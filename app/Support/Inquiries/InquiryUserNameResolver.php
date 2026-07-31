<?php

namespace App\Support\Inquiries;

use App\Models\User;

class InquiryUserNameResolver
{
    /**
     * @var array<int, string>
     */
    private static array $cache = [];

    public static function resolve(int $userId): string
    {
        if ($userId <= 0) {
            return '—';
        }

        if (! array_key_exists($userId, self::$cache)) {
            $user = User::query()
                ->select(['hr_id', 'hr_first_name', 'hr_last_name', 'hr_username'])
                ->find($userId);

            if ($user === null) {
                self::$cache[$userId] = '—';
            } else {
                $name = trim($user->hr_first_name.' '.$user->hr_last_name);

                self::$cache[$userId] = $name !== '' ? $name : (string) $user->hr_username;
            }
        }

        return self::$cache[$userId];
    }
}
