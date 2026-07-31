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

        $direct = $userId > 0
            ? DB::table('user_permission')->where('userid', $userId)->get(['page', 'permit'])
            : collect();
        $group = $groupId > 0
            ? DB::table('user_groups_permission')->where('groupid', $groupId)->get(['page', 'permit'])
            : collect();

        $directByPage = $direct->groupBy(fn ($row) => trim((string) $row->page));
        $groupByPage = $group->groupBy(fn ($row) => trim((string) $row->page));
        $pages = $directByPage->keys()->merge($groupByPage->keys())->filter(fn ($page) => $page !== '')->unique();
        $allowed = [];

        foreach ($pages as $page) {
            $directRows = $directByPage->get($page, collect());
            $groupRows = $groupByPage->get($page, collect());
            $rows = $directRows->isNotEmpty() ? $directRows : $groupRows;
            $hasDeny = $rows->contains(fn ($row) => (string) $row->permit === '1');
            $hasAllow = $rows->contains(fn ($row) => (string) $row->permit === '2');

            if (! $hasDeny && $hasAllow) {
                $allowed[] = $page;
            }
        }

        return $allowed;
    }
}
