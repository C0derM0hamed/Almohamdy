<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LoginService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly OtpService $otp,
    ) {}

    public function isAuthenticated(): bool
    {
        return Session::has('hr_user_id');
    }

    public function hasPendingOtp(): bool
    {
        return Session::get('step1') === true;
    }

    public function clearPendingOtp(): void
    {
        Session::forget(self::pendingSessionKeys());
    }

    public function logout(): void
    {
        Session::forget(array_merge(self::pendingSessionKeys(), [
            'hr_user_id',
            'hm_permissions',
            'groupid',
        ]));
    }

    /**
     * @return list<string>
     */
    private static function pendingSessionKeys(): array
    {
        return [
            'step1',
            'hr_id',
            'hr_username',
            'hr_first_name',
            'hr_last_name',
            'hr_user_level',
            'hr_branch_id',
            'companies_groups_id',
            'lastPassChange',
            'department_supervisor',
            'job_title',
            'mobile',
            'email',
            'otp_channel',
            'code',
            'code_time',
            'otp_expires_at',
            'otp_failed_attempts',
        ];
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function attemptCredentials(Request $request, string $username, string $password): array
    {
        [$user, $channel] = $this->resolveUserAndChannel($username);

        if ($this->users->isUserLoginLocked($user)) {
            return [
                'success' => false,
                'message' => __('login.errors.locked'),
            ];
        }

        if (! $user
            && str_contains($username, '@')
            && $this->users->countActiveByEmail(trim($username)) > 1) {
            return [
                'success' => false,
                'message' => __('login.errors.email_not_unique'),
            ];
        }

        if (! $user || ! $this->verifyPassword($password, (string) $user->hr_password)) {
            $this->incrementFailedAttempts($user?->hr_username ?? $username);

            return [
                'success' => false,
                'message' => __('login.errors.invalid_credentials'),
            ];
        }

        if (! $this->canReceiveOtp($user, $channel)) {
            return [
                'success' => false,
                'message' => $channel === 'sms'
                    ? __('login.errors.mobile_required')
                    : __('login.errors.email_required'),
            ];
        }

        $request->session()->regenerate();
        $this->storePendingUserSession($user, $channel);

        $otpResult = $this->otp->generateAndStore($user, $channel);

        if (! $otpResult['success']) {
            $this->clearPendingOtp();

            return [
                'success' => false,
                'message' => $otpResult['message'] ?? __('login.errors.otp_send_failed'),
            ];
        }

        Session::forget('LoggedAttempts');
        $this->users->clearFailedLogin((string) $user->hr_username);

        return ['success' => true];
    }

    /**
     * Resolves the login identifier to an active user and the OTP channel to use.
     *
     * Username and email matching is unchanged from the original behavior
     * (delegated to findActiveByUsernameOrEmail, tried first). A numeric,
     * phone-shaped identifier that doesn't match a username is tried against
     * the mobile column as a new, additive lookup path.
     *
     * @return array{0: ?User, 1: string}
     */
    private function resolveUserAndChannel(string $login): array
    {
        $login = trim($login);

        if (str_contains($login, '@')) {
            return [$this->users->findActiveByUsernameOrEmail($login), 'email'];
        }

        $user = $this->users->findActiveByUsernameOrEmail($login);

        if ($user !== null) {
            return [$user, 'email'];
        }

        $digits = preg_replace('/\D+/', '', $login) ?? '';

        if (strlen($digits) >= 9) {
            $mobileUser = $this->users->findActiveByMobile($login);

            if ($mobileUser !== null) {
                return [$mobileUser, 'sms'];
            }
        }

        return [null, 'email'];
    }

    public function pendingUser(): ?User
    {
        $hrId = (int) Session::get('hr_id', 0);

        if ($hrId <= 0) {
            return null;
        }

        return $this->users->findById($hrId);
    }

    private function verifyPassword(string $plain, string $storedHash): bool
    {
        $normalized = strtolower(trim($storedHash));

        return $normalized !== ''
            && hash_equals($normalized, hash('sha256', $plain));
    }

    private function canReceiveOtp(User $user, string $channel): bool
    {
        return $channel === 'sms'
            ? $this->hasValidMobile($user)
            : $this->hasValidEmail($user);
    }

    private function hasValidMobile(User $user): bool
    {
        return strlen((string) $user->mobile) >= 9;
    }

    private function hasValidEmail(User $user): bool
    {
        $email = trim((string) $user->hr_email_address);

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function storePendingUserSession(User $user, string $channel): void
    {
        Session::put([
            'hr_id' => $user->hr_id,
            'hr_username' => $user->hr_username,
            'hr_first_name' => $user->hr_first_name,
            'hr_last_name' => $user->hr_last_name,
            'hr_user_level' => (int) $user->hr_user_level,
            'hr_branch_id' => $user->branch_id,
            'companies_groups_id' => $user->companies_groups_id,
            'groupid' => (int) $user->groupid,
            'lastPassChange' => $user->lastPassChange,
            'department_supervisor' => $user->department_supervisor,
            'job_title' => $user->job_title,
            'mobile' => $user->mobile,
            'email' => $user->hr_email_address,
            'otp_channel' => $channel,
            'step1' => true,
        ]);
    }

    private function incrementFailedAttempts(string $username): void
    {
        $attempts = (int) Session::get('LoggedAttempts', 0) + 1;
        Session::put('LoggedAttempts', $attempts);

        if ($attempts >= 3) {
            $this->users->recordFailedLogin($username, $attempts);
            Session::put('LoggedAttempts', 0);
        }
    }
}
