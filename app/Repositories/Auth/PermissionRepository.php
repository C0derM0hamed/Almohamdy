<?php

namespace App\Repositories\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissionRepository
{
    /**
     * Resolve every stored permission to its effective decision and source.
     * A direct row always takes precedence over group rows; within either
     * source an explicit deny (`permit=1`) wins over an allow (`permit=2`).
     *
     * @return array<string, array{decision: 'allow'|'deny'|'none', source: 'direct'|'group'|'none'}>
     */
    public function permissionStatesForUser(int $userId, int $groupId): array
    {
        if ($userId <= 0 && $groupId <= 0) {
            return [];
        }

        $direct = $userId > 0 && Schema::hasTable('user_permission')
            ? DB::table('user_permission')->where('userid', $userId)->get(['page', 'permit'])
            : collect();
        $group = $groupId > 0 && Schema::hasTable('user_groups_permission')
            ? DB::table('user_groups_permission')->where('groupid', $groupId)->get(['page', 'permit'])
            : collect();

        $directByPage = $direct->groupBy(fn ($row) => trim((string) $row->page));
        $groupByPage = $group->groupBy(fn ($row) => trim((string) $row->page));
        $pages = $directByPage->keys()->merge($groupByPage->keys())
            ->filter(fn ($page) => $page !== '' && $page !== '0')->unique();
        $states = [];

        foreach ($pages as $page) {
            $directRows = $directByPage->get($page, collect());
            $groupRows = $groupByPage->get($page, collect());
            $rows = $directRows->isNotEmpty() ? $directRows : $groupRows;
            $source = $directRows->isNotEmpty() ? 'direct' : 'group';
            $hasDeny = $rows->contains(fn ($row) => (string) $row->permit === '1');
            $hasAllow = $rows->contains(fn ($row) => (string) $row->permit === '2');

            $states[$page] = [
                'decision' => $hasDeny ? 'deny' : ($hasAllow ? 'allow' : 'none'),
                'source' => $source,
            ];
        }

        return $states;
    }

    /**
     * @return list<string>
     */
    public function permissionsForUser(int $userId, int $groupId): array
    {
        return collect($this->permissionStatesForUser($userId, $groupId))
            ->filter(fn (array $state): bool => $state['decision'] === 'allow')
            ->keys()->values()->all();
    }
}
