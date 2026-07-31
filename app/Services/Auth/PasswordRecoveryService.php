<?php

namespace App\Services\Auth;

use App\Repositories\UserRepository;
use App\Services\Sms\SmsGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class PasswordRecoveryService
{
    private const PREFIX = 'password_recovery.';

    public function __construct(
        private readonly UserRepository $users,
        private readonly SmsGateway $sms,
    ) {}

    public function begin(Request $request, string $username, string $mobile): void
    {
        $this->clear();

        $user = $this->users->findActiveByUsernameAndMobile($username, $mobile);
        $length = max(4, min(8, (int) config('hm.password_recovery.otp_length', 6)));
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        $salt = bin2hex(random_bytes(32));
        $now = time();

        $request->session()->put([
            self::PREFIX.'otp_hash' => $this->hashOtp($code, $salt),
            self::PREFIX.'otp_salt' => $salt,
            self::PREFIX.'expires_at' => $now + (int) config('hm.password_recovery.expiry_seconds', 120),
            self::PREFIX.'attempts' => 0,
            self::PREFIX.'user_id' => $user?->hr_id,
            self::PREFIX.'pending' => true,
        ]);

        if ($user !== null) {
            $result = $this->sms->send(
                (string) $user->mobile,
                __('password_recovery.sms', ['code' => $code, 'seconds' => config('hm.password_recovery.expiry_seconds', 120)]),
            );

            if (! $result['ok']) {
                Log::warning('password_recovery.sms_failed', [
                    'hr_id' => $user->hr_id,
                    'provider' => $result['provider'],
                ]);
            }
        }
    }

    public function hasPendingChallenge(): bool
    {
        return Session::get(self::PREFIX.'pending') === true;
    }

    public function verify(Request $request, string $code): bool
    {
        if (! $this->hasPendingChallenge()
            || time() > (int) Session::get(self::PREFIX.'expires_at', 0)) {
            $this->clear();

            return false;
        }

        $attempts = (int) Session::get(self::PREFIX.'attempts', 0);
        $maximum = (int) config('hm.password_recovery.max_attempts', 5);

        if ($attempts >= $maximum) {
            $this->clear();

            return false;
        }

        Session::put(self::PREFIX.'attempts', $attempts + 1);

        $expected = (string) Session::get(self::PREFIX.'otp_hash', '');
        $actual = $this->hashOtp($code, (string) Session::get(self::PREFIX.'otp_salt', ''));
        $userId = (int) Session::get(self::PREFIX.'user_id', 0);

        if ($expected === '' || $userId <= 0 || ! hash_equals($expected, $actual)) {
            return false;
        }

        $request->session()->regenerate();
        Session::forget([
            self::PREFIX.'otp_hash',
            self::PREFIX.'otp_salt',
            self::PREFIX.'expires_at',
            self::PREFIX.'attempts',
            self::PREFIX.'pending',
        ]);
        Session::put([
            self::PREFIX.'authorized_user_id' => $userId,
            self::PREFIX.'authorization_marker' => bin2hex(random_bytes(32)),
            self::PREFIX.'authorization_expires_at' => time() + (int) config('hm.password_recovery.authorization_seconds', 300),
        ]);
        Session::forget(self::PREFIX.'user_id');

        return true;
    }

    public function isAuthorized(): bool
    {
        return (int) Session::get(self::PREFIX.'authorized_user_id', 0) > 0
            && filled(Session::get(self::PREFIX.'authorization_marker'))
            && time() <= (int) Session::get(self::PREFIX.'authorization_expires_at', 0);
    }

    public function reset(string $password): bool
    {
        if (! $this->isAuthorized()) {
            $this->clear();

            return false;
        }

        $userId = (int) Session::get(self::PREFIX.'authorized_user_id');
        $updated = $this->users->resetLegacyPassword($userId, $password);

        if ($updated) {
            $this->clear();
        }

        return $updated;
    }

    public function clear(): void
    {
        Session::forget([
            self::PREFIX.'otp_hash', self::PREFIX.'otp_salt', self::PREFIX.'expires_at',
            self::PREFIX.'attempts', self::PREFIX.'user_id', self::PREFIX.'pending',
            self::PREFIX.'authorized_user_id', self::PREFIX.'authorization_marker',
            self::PREFIX.'authorization_expires_at',
        ]);
    }

    private function hashOtp(string $code, string $salt): string
    {
        return hash_hmac('sha256', $code, $salt.'|'.(string) config('app.key'));
    }
}
