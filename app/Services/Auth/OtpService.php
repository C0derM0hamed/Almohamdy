<?php

namespace App\Services\Auth;

use App\Mail\LoginOtpMail;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Dashboard\NavigationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Throwable;

class OtpService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PermissionService $permissions,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{success: bool, message?: string}
     */
    public function generateAndStore(User $user): array
    {
        $code = $this->generateCode();
        $now = time();

        Session::put([
            'code' => $code,
            'code_time' => $now,
            'otp_expires_at' => $now + $this->expirySeconds(),
            'otp_failed_attempts' => 0,
        ]);

        if ($this->isDemoMode()) {
            return ['success' => true];
        }

        return $this->sendOtpEmail($user, $code);
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function resend(User $user): array
    {
        if (! Session::get('step1')) {
            return [
                'success' => false,
                'message' => __('otp.errors.session_expired'),
            ];
        }

        if ($this->remainingSeconds() > 0) {
            return [
                'success' => false,
                'message' => __('otp.errors.resend_wait', ['seconds' => $this->remainingSeconds()]),
            ];
        }

        return $this->generateAndStore($user);
    }

    /**
     * @return array{success: bool, message?: string, redirect?: string}
     */
    public function verify(Request $request, string $submittedCode): array
    {
        if (! Session::get('step1')) {
            return [
                'success' => false,
                'message' => __('otp.errors.session_expired'),
            ];
        }

        if ($this->isExpired()) {
            return [
                'success' => false,
                'message' => __('otp.errors.expired'),
            ];
        }

        if ($this->hasExceededAttempts()) {
            return [
                'success' => false,
                'message' => __('otp.errors.max_attempts'),
            ];
        }

        if (! hash_equals((string) Session::get('code'), $submittedCode)) {
            Session::put('otp_failed_attempts', (int) Session::get('otp_failed_attempts', 0) + 1);

            return [
                'success' => false,
                'message' => __('otp.errors.invalid_code'),
            ];
        }

        $hrId = (int) Session::get('hr_id');
        $request->session()->regenerate();

        Session::put('hr_user_id', $hrId);
        Session::forget([
            'step1',
            'code',
            'code_time',
            'otp_expires_at',
            'otp_failed_attempts',
        ]);

        $this->users->touchLastLogin($hrId);

        $groupId = (int) Session::get('groupid', 0);

        if ($groupId <= 0) {
            $user = $this->users->findById($hrId);

            if ($user !== null) {
                Session::put([
                    'companies_groups_id' => $user->companies_groups_id,
                    'hr_branch_id' => $user->branch_id,
                    'groupid' => (int) $user->groupid,
                ]);
                $groupId = (int) $user->groupid;
            }
        }

        if ($hrId > 0) {
            $this->permissions->refreshSessionPermissions($hrId, $groupId);
        }

        $level = (int) Session::get('hr_user_level');

        return [
            'success' => true,
            'redirect' => $this->dashboardRouteForLevel($level),
        ];
    }

    public function remainingSeconds(): int
    {
        $expiresAt = (int) Session::get('otp_expires_at', 0);

        return max(0, $expiresAt - time());
    }

    public function dashboardRouteForLevel(int $level): string
    {
        return route($this->navigation->homeRouteName());
    }

    public function isDemoMode(): bool
    {
        return (bool) config('hm.otp.demo_mode', true);
    }

    private function generateCode(): string
    {
        $length = $this->codeLength();

        if ($this->isDemoMode()) {
            $demo = preg_replace('/\D+/', '', (string) config('hm.otp.demo_code', str_repeat('1', $length))) ?? '';

            return str_pad(substr($demo, 0, $length), $length, '1');
        }

        $min = 10 ** ($length - 1);
        $max = (10 ** $length) - 1;

        return (string) random_int($min, $max);
    }

    public function codeLength(): int
    {
        return max(4, min(8, (int) config('hm.otp.length', 6)));
    }

    /**
     * @return array{success: bool, message?: string}
     */
    private function sendOtpEmail(User $user, string $code): array
    {
        $email = trim((string) $user->hr_email_address);

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => __('login.errors.email_required'),
            ];
        }

        $recipientName = trim($user->hr_first_name.' '.$user->hr_last_name) ?: (string) $user->hr_username;
        $expiryMinutes = max(1, (int) ceil($this->expirySeconds() / 60));

        // Send during the request (not afterResponse). The login interstitial
        // redirects immediately, and Apache can abort deferred callbacks before SMTP finishes.
        try {
            ignore_user_abort(true);

            Mail::to($email)->send(new LoginOtpMail(
                code: $code,
                recipientName: $recipientName,
                expiryMinutes: $expiryMinutes,
            ));

            logger()->info('HM OTP email sent', [
                'email' => $email,
                'hr_id' => $user->hr_id,
            ]);
        } catch (Throwable $exception) {
            logger()->error('HM OTP email failed', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('login.errors.otp_send_failed'),
            ];
        }

        return ['success' => true];
    }

    private function expirySeconds(): int
    {
        return (int) config('hm.otp.expiry_seconds', 120);
    }

    private function isExpired(): bool
    {
        return $this->remainingSeconds() <= 0;
    }

    private function hasExceededAttempts(): bool
    {
        return (int) Session::get('otp_failed_attempts', 0) >= (int) config('hm.otp.max_attempts', 5);
    }
}
