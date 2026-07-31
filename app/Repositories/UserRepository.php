<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            $query->where('hr_email_address', $login);
        } else {
            $query->where('hr_username', $login);
        }

        return $query->first();
    }

    public function findById(int $hrId): ?User
    {
        return User::query()
            ->select(self::AUTH_COLUMNS)
            ->whereKey($hrId)
            ->where('activated', '1')
            ->first();
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
