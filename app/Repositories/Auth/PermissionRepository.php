<?php

namespace App\Repositories\Auth;

use Illuminate\Support\Facades\DB;

class PermissionRepository
{
    /**
     * @return list<string>
     */
    public function permissionsForUser(int $userId, int $groupId): array
    {
        if ($userId <= 0 && $groupId <= 0) {
            return [];
        }

        $bindings = [];
        $parts = [];

        if ($userId > 0) {
            $parts[] = 'SELECT page FROM user_permission WHERE userid = ? AND permit NOT IN (?, ?)';
            $bindings[] = $userId;
            $bindings[] = '';
            $bindings[] = '0';
        }

        if ($groupId > 0) {
            $parts[] = 'SELECT page FROM user_groups_permission WHERE groupid = ? AND permit NOT IN (?, ?)';
            $bindings[] = $groupId;
            $bindings[] = '';
            $bindings[] = '0';
        }

        $rows = DB::select(implode(' UNION ALL ', $parts), $bindings);

        $permissions = [];

        foreach ($rows as $row) {
            $page = trim((string) ($row->page ?? ''));

            if ($page !== '') {
                $permissions[$page] = $page;
            }
        }

        return array_values($permissions);
    }
}
