<?php

namespace App\Services\Auth;

use App\Mail\LoginOtpMail;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Dashboard\NavigationService;
use App\Services\Sms\SmsGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Throwable;

class OtpService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PermissionService $permissions,
        private readonly NavigationService $navigation,
        private readonly SmsGateway $sms,
    ) {}

    /**
     * @return array{success: bool, message?: string}
     */
    public function generateAndStore(User $user, string $channel): array
    {
        $code = $this->generateCode();
        $now = time();

        Session::put([
            'code' => Hash::make($code),
            'code_time' => $now,
            'otp_expires_at' => $now + $this->expirySeconds(),
            'otp_failed_attempts' => 0,
            'otp_channel' => $channel,
        ]);

        if ($this->demoMode()) {
            Session::put('otp_demo_code', $code);
            logger()->info('HM OTP demo code', [
                'hr_id' => $user->hr_id,
                'code' => $code,
            ]);
        } else {
            Session::forget('otp_demo_code');
        }

        return $channel === 'sms'
            ? $this->sendOtpSms($user, $code)
            : $this->sendOtpEmail($user, $code);
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

        $secondsSinceSent = time() - (int) Session::get('code_time', 0);
        $cooldown = $this->resendCooldownSeconds();

        if ($secondsSinceSent < $cooldown) {
            return [
                'success' => false,
                'message' => __('otp.errors.resend_wait', ['seconds' => $cooldown - $secondsSinceSent]),
            ];
        }

        $channel = (string) Session::get('otp_channel', 'email');

        return $this->generateAndStore($user, $channel);
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

        if (! Hash::check($submittedCode, (string) Session::get('code'))) {
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
            'otp_channel',
            'otp_demo_code',
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

    /**
     * Seconds left on the resend cooldown. This is deliberately separate from
     * remainingSeconds() (OTP validity) so the UI countdown matches the backend
     * cooldown rather than the much longer expiry window.
     */
    public function resendAvailableInSeconds(): int
    {
        $sentAt = (int) Session::get('code_time', 0);

        if ($sentAt <= 0) {
            return 0;
        }

        return max(0, ($sentAt + $this->resendCooldownSeconds()) - time());
    }

    public function resendCooldown(): int
    {
        return $this->resendCooldownSeconds();
    }

    public function dashboardRouteForLevel(int $level): string
    {
        return $this->navigation->homeUrl();
    }

    private function generateCode(): string
    {
        $length = $this->codeLength();

        $min = 10 ** ($length - 1);
        $max = (10 ** $length) - 1;

        return (string) random_int($min, $max);
    }

    public function codeLength(): int
    {
        return max(4, min(8, (int) config('hm.otp.length', 6)));
    }

    public function demoCode(): ?string
    {
        if (! $this->demoMode()) {
            return null;
        }

        $code = (string) Session::get('otp_demo_code', '');

        return $code !== '' ? $code : null;
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

    /**
     * @return array{success: bool, message?: string}
     */
    private function sendOtpSms(User $user, string $code): array
    {
        $mobile = trim((string) $user->mobile);

        if (strlen(preg_replace('/\D+/', '', $mobile) ?? '') < 9) {
            return [
                'success' => false,
                'message' => __('login.errors.mobile_required'),
            ];
        }

        $expiryMinutes = max(1, (int) ceil($this->expirySeconds() / 60));
        $message = __('otp.sms', ['code' => $code, 'minutes' => $expiryMinutes], 'ar');

        $result = $this->sms->send($mobile, $message);

        if (! $result['ok']) {
            logger()->error('HM OTP sms failed', [
                'hr_id' => $user->hr_id,
                'provider' => $result['provider'],
                'error' => $result['error'],
            ]);

            return [
                'success' => false,
                'message' => __('login.errors.otp_send_failed'),
            ];
        }

        logger()->info('HM OTP sms sent', [
            'hr_id' => $user->hr_id,
            'provider' => $result['provider'],
        ]);

        return ['success' => true];
    }

    private function demoMode(): bool
    {
        return (bool) config('hm.otp.demo_mode', false)
            && app()->environment(['local', 'testing']);
    }

    private function expirySeconds(): int
    {
        return (int) config('hm.otp.expiry_seconds', 300);
    }

    private function resendCooldownSeconds(): int
    {
        return max(15, (int) config('hm.otp.resend_cooldown_seconds', 60));
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
