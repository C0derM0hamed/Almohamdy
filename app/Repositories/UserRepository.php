<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserRepository
{
    /**
     * @var list<string>
     */
    private const AUTH_COLUMNS = [
        'hr_id',
        'hr_first_name',
        'hr_last_name',
        'hr_email_address',
        'hr_username',
        'hr_password',
        'hr_user_level',
        'branch_id',
        'mobile',
        'companies_groups_id',
        'groupid',
        'hr_last_login',
        'lastPassChange',
        'department_supervisor',
        'job_title',
        'activated',
        'failed_login_attempts',
        'last_failed_login',
    ];

    public function findActiveByUsernameOrEmail(string $login): ?User
    {
        $query = User::query()
            ->select(self::AUTH_COLUMNS)
            ->where('activated', '1');

        // Prefer a single equality so username / email indexes can be used.
        if (str_contains($login, '@')) {
            // An address shared by more than one active account (the dedicated
            // demo accounts) must never be auto-resolved to an arbitrary user;
            // those accounts sign in by username. Unique addresses are
            // unaffected.
            $matches = $query->where('hr_email_address', $login)->limit(2)->get();

            return $matches->count() === 1 ? $matches->first() : null;
        }

        return $query->where('hr_username', $login)->first();
    }

    /**
     * Number of active accounts sharing an email address. Used to tell an
     * unknown address apart from one that requires username login.
     */
    public function countActiveByEmail(string $email): int
    {
        return User::query()
            ->where('activated', '1')
            ->where('hr_email_address', $email)
            ->count();
    }

    public function findById(int $hrId): ?User
    {
        return User::query()
            ->select(self::AUTH_COLUMNS)
            ->whereKey($hrId)
            ->where('activated', '1')
            ->first();
    }

    /**
     * Matches an active user's mobile number regardless of leading 0 / 966
     * country code / spaces / dashes. Legacy `mobile` values are stored in a
     * few different shapes, so this compares a small set of digit-only
     * candidates in PHP rather than assuming a fixed stored format/length.
     */
    public function findActiveByMobile(string $mobile): ?User
    {
        $candidates = $this->mobileCandidates($mobile);

        if ($candidates === []) {
            return null;
        }

        return User::query()
            ->select(self::AUTH_COLUMNS)
            ->where('activated', '1')
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->get()
            ->first(function (User $user) use ($candidates): bool {
                $stored = preg_replace('/\D+/', '', (string) $user->mobile) ?? '';

                return $stored !== '' && in_array($stored, $candidates, true);
            });
    }

    /**
     * @return list<string>
     */
    private function mobileCandidates(string $mobile): array
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        if ($digits === '') {
            return [];
        }

        $candidates = [$digits];

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
            $candidates[] = $digits;
        }

        if (str_starts_with($digits, '966')) {
            $local = substr($digits, 3);
            $candidates[] = $local;
            $candidates[] = '0'.$local;
        } elseif (str_starts_with($digits, '0')) {
            $local = substr($digits, 1);
            $candidates[] = $local;
            $candidates[] = '966'.$local;
        } else {
            $candidates[] = '0'.$digits;
            $candidates[] = '966'.$digits;
        }

        return array_values(array_unique(array_filter($candidates, static fn (string $c): bool => $c !== '')));
    }

    public function findActiveByUsernameAndMobile(string $username, string $mobile): ?User
    {
        return User::query()
            ->select(['hr_id', 'hr_username', 'mobile', 'activated'])
            ->where('hr_username', $username)
            ->where('mobile', $mobile)
            ->where('activated', '1')
            ->first();
    }

    public function resetLegacyPassword(int $hrId, string $password): bool
    {
        if ($hrId <= 0
            || ! Schema::hasTable('ra_users')
            || ! Schema::hasColumns('ra_users', ['hr_id', 'hr_password'])) {
            return false;
        }

        $values = ['hr_password' => hash('sha256', $password)];

        if (Schema::hasColumn('ra_users', 'lastPassChange')) {
            $values['lastPassChange'] = (string) time();
        }

        return DB::table('ra_users')
            ->where('hr_id', $hrId)
            ->where('activated', '1')
            ->limit(1)
            ->update($values) === 1;
    }

    public function changeLegacyPassword(int $hrId, string $currentPassword, string $newPassword): bool
    {
        if ($hrId <= 0 || ! Schema::hasTable('ra_users')
            || ! Schema::hasColumns('ra_users', ['hr_id', 'hr_password'])) {
            return false;
        }

        $user = DB::table('ra_users')
            ->where('hr_id', $hrId)
            ->where('activated', '1')
            ->first(['hr_password']);

        if ($user === null || ! hash_equals(strtolower((string) $user->hr_password), hash('sha256', $currentPassword))) {
            return false;
        }

        return $this->resetLegacyPassword($hrId, $newPassword);
    }

    public function isUserLoginLocked(?User $user): bool
    {
        if ($user === null || blank($user->last_failed_login)) {
            return false;
        }

        return Carbon::parse($user->last_failed_login)->gt(now()->subMinutes(5));
    }

    public function recordFailedLogin(string $username, int $attempts): void
    {
        DB::table('ra_users')
            ->where(function ($query) use ($username) {
                if (str_contains($username, '@')) {
                    $query->where('hr_email_address', $username);
                } else {
                    $query->where('hr_username', $username);
                }
            })
            ->limit(1)
            ->update([
                'failed_login_attempts' => $attempts,
                'last_failed_login' => now(),
            ]);
    }

    public function clearFailedLogin(string $username): void
    {
        DB::table('ra_users')
            ->where(function ($query) use ($username) {
                if (str_contains($username, '@')) {
                    $query->where('hr_email_address', $username);
                } else {
                    $query->where('hr_username', $username);
                }
            })
            ->limit(1)
            ->update([
                'failed_login_attempts' => 0,
                'last_failed_login' => null,
            ]);
    }

    public function touchLastLogin(int $hrId): void
    {
        DB::table('ra_users')
            ->where('hr_id', $hrId)
            ->limit(1)
            ->update(['hr_last_login' => now()]);
    }
}
