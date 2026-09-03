<?php

namespace App\Repositories\SystemAdministration;

use App\Models\User;
use App\Services\Auth\PermissionRegistry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserPermissionAdminRepository
{
    public function __construct(private readonly PermissionRegistry $registry) {}
    public function users(?int $companyId, ?int $branchId, int $perPage = 20): LengthAwarePaginator
    {
        $columns = ['hr_id', 'hr_first_name', 'hr_last_name', 'hr_email_address', 'hr_username', 'hr_user_level', 'branch_id', 'companies_groups_id', 'groupid', 'activated'];

        foreach (['mobile', 'job_title'] as $optionalColumn) {
            if (Schema::hasColumn('ra_users', $optionalColumn)) {
                $columns[] = $optionalColumn;
            }
        }

        $users = User::query()
            ->select($columns)
            ->when($companyId !== null, fn ($query) => $query->where('companies_groups_id', $companyId))
            ->when($branchId !== null, function ($query) use ($branchId): void {
                $query->where(function ($scoped) use ($branchId): void {
                    $scoped->where('branch_id', $branchId);

                    if (Schema::hasTable('ra_user_branches')) {
                        $scoped->orWhereExists(function ($branchQuery) use ($branchId): void {
                            $branchQuery->selectRaw('1')
                                ->from('ra_user_branches')
                                ->whereColumn('ra_user_branches.user_id', 'ra_users.hr_id')
                                ->where('ra_user_branches.branch_id', $branchId);
                        });
                    }
                });
            })
            ->orderBy('hr_first_name')
            ->orderBy('hr_id')
            ->paginate($perPage);

        $this->hydrateListPresentation($users);

        return $users;
    }

    public function findScoped(int $id, ?int $companyId, ?int $branchId): ?User
    {
        return User::query()
            ->whereKey($id)
            ->when($companyId !== null, fn ($query) => $query->where('companies_groups_id', $companyId))
            ->when($branchId !== null, function ($query) use ($branchId): void {
                $query->where(function ($scoped) use ($branchId): void {
                    $scoped->where('branch_id', $branchId);

                    if (Schema::hasTable('ra_user_branches')) {
                        $scoped->orWhereExists(function ($branchQuery) use ($branchId): void {
                            $branchQuery->selectRaw('1')
                                ->from('ra_user_branches')
                                ->whereColumn('ra_user_branches.user_id', 'ra_users.hr_id')
                                ->where('ra_user_branches.branch_id', $branchId);
                        });
                    }
                });
            })
            ->first();
    }

    /**
     * Return all branches assigned to a user. The legacy branch_id remains a
     * fallback so old installations and test schemas continue to work before
     * the new mapping table is migrated.
     *
     * @return list<int>
     */
    public function branchIds(int $userId, ?int $legacyBranchId = null): array
    {
        if (Schema::hasTable('ra_user_branches')) {
            $ids = DB::table('ra_user_branches')
                ->where('user_id', $userId)
                ->orderBy('branch_id')
                ->pluck('branch_id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->values()
                ->all();

            if ($ids !== []) {
                return $ids;
            }
        }

        return $legacyBranchId !== null && $legacyBranchId > 0 ? [$legacyBranchId] : [];
    }

    /**
     * Persist the selected branch assignments while retaining branch_id as the
     * primary branch consumed by the legacy modules and session scope.
     *
     * @param list<int> $branchIds
     */
    public function replaceUserBranches(int $userId, array $branchIds): void
    {
        if (! Schema::hasTable('ra_user_branches')) {
            return;
        }

        DB::table('ra_user_branches')->where('user_id', $userId)->delete();

        $now = now();
        $rows = collect($branchIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->map(static fn (int $branchId): array => [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('ra_user_branches')->insert($rows);
        }
    }

    public function groups(?int $branchId = null): Collection
    {
        if ($branchId !== null) {
            return DB::table('user_groups')->where('publish', 1)->orderBy('name_ar')->get();
        }

        return DB::table('user_groups')->where('publish', 1)->orderBy('name_ar')->get();
    }

    public function branches(?int $companyId): Collection
    {
        return DB::table('branches')->when($companyId !== null, fn ($query) => $query->where('companies_groups_id', $companyId))->orderBy('name_ar')->get();
    }

    public function companies(): Collection
    {
        return DB::table('companies_groups')->orderBy('name_ar')->get();
    }

    public function permissionCatalog(): Collection
    {
        $known = $this->registry->permissions()->keyBy('code');
        $legacy = DB::query()->fromSub(
            DB::table('user_permission')->select('page')->where('page', '<>', '0')
                ->union(DB::table('user_groups_permission')->select('page')->where('page', '<>', '0')),
            'permission_pages'
        )->select('page')->distinct()->orderBy('page')->pluck('page');

        foreach ($legacy as $page) {
            $code = $this->registry->canonical((string) $page);
            if (! $known->has($code)) {
                $known->put($code, [
                    'code' => $code,
                    'category' => 'legacy',
                    'category_order' => 99,
                    'label' => $code,
                    'description' => 'صلاحية قديمة محفوظة للتوافق',
                    'icon' => 'bi-clock-history',
                    'order' => 999,
                    'legacy_aliases' => [],
                    'routes' => [],
                ]);
            }
        }

        return $known->sortBy([['category_order', 'asc'], ['order', 'asc'], ['code', 'asc']])->values();
    }

    public function directPermissions(int $userId): Collection
    {
        return DB::table('user_permission')->where('userid', $userId)
            ->where('permit', '2')->pluck('page');
    }

    /** @return Collection<string, string> code => allow|deny */
    public function directPermissionDecisions(int $userId): Collection
    {
        return DB::table('user_permission')->where('userid', $userId)
            ->whereIn('permit', ['1', '2'])
            ->get(['page', 'permit'])
            ->groupBy('page')
            ->map(function (Collection $rows): string {
                return $rows->contains(fn ($row): bool => (string) $row->permit === '1') ? 'deny' : 'allow';
            });
    }

    public function inheritedPermissions(int $groupId): Collection
    {
        if ($groupId <= 0) {
            return collect();
        }

        return DB::table('user_groups_permission')->where('groupid', $groupId)
            ->where('permit', '2')->pluck('page');
    }

    public function replaceDirectPermissions(int $userId, array $permissions): void
    {
        $decisions = collect($permissions)->filter()->unique()
            ->mapWithKeys(fn (string $permission): array => [$permission => 'allow'])->all();
        $this->replaceDirectPermissionDecisions($userId, $decisions);
    }

    /**
     * @param array<string, 'allow'|'deny'|'inherit'> $decisions
     */
    public function replaceDirectPermissionDecisions(int $userId, array $decisions): void
    {
        $current = $this->directPermissionDecisions($userId);
        $desired = collect($decisions)
            ->filter(fn (string $decision): bool => in_array($decision, ['allow', 'deny'], true));

        $allCodes = $current->keys()->merge($desired->keys())->unique();
        foreach ($allCodes as $code) {
            $before = $current->get($code);
            $after = $desired->get($code);
            if ($before === $after) {
                continue;
            }

            DB::table('user_permission')->where('userid', $userId)->where('page', $code)->delete();
            if ($after !== null) {
                DB::table('user_permission')->insert([
                    'userid' => $userId,
                    'pageid' => 0,
                    'page' => $code,
                    'permit' => $after === 'deny' ? '1' : '2',
                ]);
            }
        }
    }

    public function permissionVersion(int $userId, int $groupId): string
    {
        $direct = DB::table('user_permission')->where('userid', $userId)
            ->whereIn('permit', ['1', '2'])->orderBy('page')->orderBy('permit')->get(['page', 'permit'])->map(fn ($row) => [(string) $row->page, (string) $row->permit]);
        $group = $groupId > 0
            ? DB::table('user_groups_permission')->where('groupid', $groupId)->whereIn('permit', ['1', '2'])->orderBy('page')->orderBy('permit')->get(['page', 'permit'])->map(fn ($row) => [(string) $row->page, (string) $row->permit])
            : collect();

        return hash('sha256', json_encode(['group' => $groupId, 'direct' => $direct, 'inherited' => $group], JSON_UNESCAPED_UNICODE));
    }

    public function auditPermissionChange(array $values): void
    {
        if (! Schema::hasTable('permission_change_logs')) {
            return;
        }

        DB::table('permission_change_logs')->insert($values);
    }

    public function permissionHistory(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        if (! Schema::hasTable('permission_change_logs')) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        return DB::table('permission_change_logs')->where('subject_user_id', $userId)
            ->orderByDesc('id')->paginate($perPage);
    }

    public function activeSuperAdministratorCount(): int
    {
        return User::query()->where('hr_user_level', '3')->where('activated', '1')->count();
    }

    private function hydrateListPresentation(LengthAwarePaginator $users): void
    {
        $items = $users->getCollection();
        $userIds = $items->pluck('hr_id')->map(static fn ($id): int => (int) $id)->all();
        $branchIdsByUser = [];

        if (Schema::hasTable('ra_user_branches') && $userIds !== []) {
            $branchIdsByUser = DB::table('ra_user_branches')
                ->whereIn('user_id', $userIds)
                ->orderBy('branch_id')
                ->get(['user_id', 'branch_id'])
                ->groupBy('user_id')
                ->map(static fn (Collection $rows): array => $rows->pluck('branch_id')->map(static fn ($id): int => (int) $id)->all())
                ->all();
        }

        $allBranchIds = $items->flatMap(function (User $user) use ($branchIdsByUser): array {
            return $branchIdsByUser[$user->hr_id] ?? [(int) $user->branch_id];
        })->unique()->filter(static fn (int $id): bool => $id > 0)->values()->all();
        $branchNames = Schema::hasTable('branches') && $allBranchIds !== []
            ? DB::table('branches')->whereIn('id', $allBranchIds)->get(['id', 'name_ar', 'name_en'])->keyBy('id')
            : collect();

        $jobTitles = Schema::hasTable('job_titles')
            ? DB::table('job_titles')->whereIn('id', $items->pluck('job_title')->filter()->all())->get(['id', 'name_ar', 'name_en'])->keyBy('id')
            : collect();

        $directByUser = Schema::hasTable('user_permission') && $userIds !== []
            ? DB::table('user_permission')->whereIn('userid', $userIds)->whereIn('permit', ['1', '2'])->get(['userid', 'page', 'permit'])->groupBy('userid')
            : collect();
        $groupIds = $items->pluck('groupid')->map(static fn ($id): int => (int) $id)->filter(static fn (int $id): bool => $id > 0)->unique()->all();
        $inheritedByGroup = Schema::hasTable('user_groups_permission') && $groupIds !== []
            ? DB::table('user_groups_permission')->whereIn('groupid', $groupIds)->where('permit', '2')->get(['groupid', 'page'])->groupBy('groupid')
            : collect();
        $catalogCodes = $this->registry->permissions()->pluck('code');
        $permissionDefinitions = $this->registry->permissions()->keyBy('code');
        $permissionCatalog = $this->permissionCatalog()->keyBy('code');
        $categoryDefinitions = $this->registry->categories()->keyBy('code');
        $registry = $this->registry;

        $items->each(function (User $user) use ($branchIdsByUser, $branchNames, $jobTitles, $directByUser, $inheritedByGroup, $catalogCodes, $permissionDefinitions, $permissionCatalog, $categoryDefinitions, $registry): void {
            $branchIds = $branchIdsByUser[$user->hr_id] ?? [(int) $user->branch_id];
            $user->setAttribute('branch_ids', $branchIds);
            $user->setAttribute('branch_names', collect($branchIds)->map(function (int $branchId) use ($branchNames): string {
                $branch = $branchNames->get($branchId);

                return $branch
                    ? (string) ($branch->name_ar ?: $branch->name_en ?: __('system_administration.users.unknown_branch'))
                    : __('system_administration.users.unknown_branch');
            })->filter()->values());

            $jobTitle = $jobTitles->get((int) ($user->job_title ?? 0));
            $user->setAttribute('department_name', $jobTitle
                ? (string) ($jobTitle->name_ar ?: $jobTitle->name_en)
                : '—');

            $directRows = collect($directByUser->get($user->hr_id, collect()));
            $direct = $directRows
                ->where('permit', '2')
                ->pluck('page')
                ->filter()
                ->unique()
                ->values();
            $denied = $directRows
                ->where('permit', '1')
                ->pluck('page')
                ->filter()
                ->unique();
            $direct = $direct->reject(fn ($permission): bool => $denied->contains($permission))->values();
            $inherited = collect($inheritedByGroup->get((int) $user->groupid, collect())->pluck('page'))->filter()->unique()->values();
            $user->setAttribute('direct_permissions', $direct);
            $user->setAttribute('inherited_permissions', $inherited);
            $effective = $inherited->reject(fn ($permission): bool => $denied->contains($permission))
                ->merge($direct)
                ->unique()
                ->sort()
                ->values();
            if ((int) $user->hr_user_level === 3) {
                $effective = $catalogCodes->merge($effective)->unique()->sort()->values();
            }
            $user->setAttribute('effective_permissions', $effective);
            $user->setAttribute('effective_permission_items', $effective->map(function ($code) use ($permissionDefinitions, $permissionCatalog, $categoryDefinitions, $registry): array {
                $code = (string) $code;
                $canonical = $registry->canonical($code);
                $definition = $permissionCatalog->get($canonical) ?? $permissionDefinitions->get($canonical, []);
                $categoryCode = (string) ($definition['category'] ?? 'legacy');
                $category = $categoryDefinitions->get($categoryCode, []);

                return [
                    'code' => $code,
                    'label' => $definition['label'] ?? 'صلاحية قديمة',
                    'icon' => $definition['icon'] ?? 'bi-shield-check',
                    'category' => $categoryCode,
                    'category_label' => $category['label'] ?? 'صلاحيات قديمة',
                    'category_description' => $category['description'] ?? '',
                    'category_icon' => $category['icon'] ?? 'bi-folder2-open',
                    'category_order' => (int) ($category['order'] ?? $definition['category_order'] ?? 999),
                ];
            })->values());
        });
    }
}
