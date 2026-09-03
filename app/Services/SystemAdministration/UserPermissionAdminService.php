<?php

namespace App\Services\SystemAdministration;

use App\Models\User;
use App\Repositories\SystemAdministration\UserPermissionAdminRepository;
use App\Services\Auth\PermissionService;
use App\Services\Auth\PermissionRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserPermissionAdminService
{
    public function __construct(
        private readonly UserPermissionAdminRepository $repository,
        private readonly PermissionService $permissions,
        private readonly PermissionRegistry $registry,
    ) {}

    public function isSuperAdministrator(): bool
    {
        return $this->permissions->isAdmin();
    }

    public function scopedUser(int $id): User
    {
        $user = $this->repository->findScoped(
            $id,
            $this->isSuperAdministrator() ? null : (int) session('companies_groups_id'),
            $this->isSuperAdministrator() ? null : (int) session('hr_branch_id'),
        );

        if ($user === null) {
            abort(403, __('system_administration.users.errors.out_of_scope'));
        }

        if (! $this->isSuperAdministrator() && (int) $user->hr_user_level === 3) {
            abort(403, __('system_administration.users.errors.out_of_scope'));
        }

        return $user;
    }

    public function listData(): array
    {
        return [
            'users' => $this->repository->users(
                $this->isSuperAdministrator() ? null : (int) session('companies_groups_id'),
                $this->isSuperAdministrator() ? null : (int) session('hr_branch_id'),
            ),
            'isSuperAdministrator' => $this->isSuperAdministrator(),
        ];
    }

    public function formData(?User $user = null): array
    {
        $direct = $user ? $this->repository->directPermissions((int) $user->hr_id) : collect();
        $inherited = $user ? $this->repository->inheritedPermissions((int) $user->groupid) : collect();
        $directDecisions = $user ? $this->repository->directPermissionDecisions((int) $user->hr_id) : collect();
        $effective = $inherited
            ->reject(fn (string $permission): bool => $directDecisions->get($permission) === 'deny')
            ->merge($directDecisions->filter(fn (string $decision): bool => $decision === 'allow')->keys())
            ->unique()->sort()->values();
        $selectedBranchIds = $user
            ? $this->repository->branchIds((int) $user->hr_id, (int) $user->branch_id)
            : [(int) session('hr_branch_id')];

        return [
            'user' => $user,
            'groups' => $this->repository->groups(),
            'branches' => $this->repository->branches($this->isSuperAdministrator() ? null : (int) session('companies_groups_id')),
            'companies' => $this->isSuperAdministrator() ? $this->repository->companies() : collect(),
            'permissionCatalog' => $this->repository->permissionCatalog(),
            'directPermissions' => $direct,
            'inheritedPermissions' => $inherited,
            'effectivePermissions' => $effective,
            'selectedBranchIds' => $selectedBranchIds,
            'isSuperAdministrator' => $this->isSuperAdministrator(),
        ];
    }

    public function permissionData(User $user): array
    {
        $catalog = $this->repository->permissionCatalog();
        $direct = $this->repository->directPermissionDecisions((int) $user->hr_id);
        $inherited = $this->repository->inheritedPermissions((int) $user->groupid)->unique()->values();
        $targetIsSuperAdministrator = (int) $user->hr_user_level === 3;
        $categories = collect(config('permissions.categories', []))->keyBy('code');

        $items = $catalog->map(function (array|object $item) use ($direct, $inherited, $targetIsSuperAdministrator): array {
            $item = (array) $item;
            $code = (string) $item['code'];
            $directDecision = $direct->get($code);
            $inheritedAllowed = $inherited->contains($code);
            $legacyInherited = $inheritedAllowed;
            // Legacy group grants are materialized as direct account grants
            // when this screen is opened/saved. The UI no longer exposes
            // groups as a permission source.
            if ($directDecision === null && $inheritedAllowed) {
                $directDecision = 'allow';
                $inheritedAllowed = false;
            }
            // Level 3 is an explicit all-access administrator.  Reflect that
            // in the management screen even when the access is not materialised
            // as hundreds of redundant direct rows in the legacy tables.
            $effective = $targetIsSuperAdministrator
                ? true
                : ($directDecision === 'deny' ? false : ($directDecision === 'allow' || $inheritedAllowed));

            return $item + [
                'direct_decision' => $targetIsSuperAdministrator ? 'allow' : ($directDecision ?? 'inherit'),
                'inherited' => $legacyInherited,
                'effective' => $effective,
            ];
        });

        return [
            'user' => $user,
            'categories' => $categories,
            'permissionsByCategory' => $items->groupBy('category'),
            'effectivePermissionCount' => $items->where('effective', true)->count(),
            'permissionsVersion' => $this->repository->permissionVersion((int) $user->hr_id, (int) $user->groupid),
            'isSuperAdministrator' => $this->isSuperAdministrator(),
        ];
    }

    public function updatePermissions(User $user, array $data, array $requestContext = []): void
    {
        if ((int) $user->hr_id === (int) session('hr_user_id')) {
            throw ValidationException::withMessages(['decisions' => __('system_administration.users.errors.self_security_change')]);
        }

        $expectedVersion = $this->repository->permissionVersion((int) $user->hr_id, (int) $user->groupid);
        if (! hash_equals($expectedVersion, (string) ($data['permissions_version'] ?? ''))) {
            throw ValidationException::withMessages(['permissions_version' => 'تم تعديل صلاحيات هذا المستخدم بواسطة مدير آخر. حدّث الصفحة ثم أعد المراجعة.']);
        }

        $submitted = collect($data['decisions'] ?? [])
            ->mapWithKeys(fn ($decision, $code) => [$this->registry->canonical((string) $code) => (string) $decision]);
        $catalogCodes = $this->repository->permissionCatalog()->pluck('code')->all();
        if ($submitted->keys()->diff($catalogCodes)->isNotEmpty()
            || $submitted->contains(fn (string $decision): bool => ! in_array($decision, ['allow', 'deny', 'inherit'], true))) {
            throw ValidationException::withMessages(['decisions' => __('system_administration.users.errors.invalid_permission')]);
        }

        if (! $this->isSuperAdministrator()) {
            $ownPermissions = (array) session('hm_permissions', []);
            $attemptedAllows = $submitted->filter(fn (string $decision): bool => $decision === 'allow')->keys()->all();
            if (array_diff($attemptedAllows, $ownPermissions) !== []) {
                abort(403, __('system_administration.users.errors.privilege_escalation'));
            }
        }

        $before = $this->repository->directPermissionDecisions((int) $user->hr_id)->all();
        $desired = $submitted->all();
        DB::transaction(function () use ($user, $desired, $before, $requestContext): void {
            $this->repository->replaceDirectPermissionDecisions((int) $user->hr_id, $desired);
            $after = $this->repository->directPermissionDecisions((int) $user->hr_id)->all();
            $this->repository->auditPermissionChange([
                'actor_user_id' => (int) session('hr_user_id', 0),
                'subject_user_id' => (int) $user->hr_id,
                'action' => 'permissions.updated',
                'before_state' => json_encode($before, JSON_UNESCAPED_UNICODE),
                'after_state' => json_encode($after, JSON_UNESCAPED_UNICODE),
                'added_permissions' => json_encode(array_keys(array_diff_assoc($after, $before)), JSON_UNESCAPED_UNICODE),
                'removed_permissions' => json_encode(array_keys(array_diff_assoc($before, $after)), JSON_UNESCAPED_UNICODE),
                'request_id' => (string) ($requestContext['request_id'] ?? Str::uuid()),
                'ip_address' => (string) ($requestContext['ip_address'] ?? ''),
                'user_agent' => (string) ($requestContext['user_agent'] ?? ''),
                'created_at' => now(),
            ]);
        });
    }

    public function permissionHistory(User $user): array
    {
        return ['user' => $user, 'history' => $this->repository->permissionHistory((int) $user->hr_id)];
    }

    public function create(array $data): User
    {
        $data = $this->normalizeBranchData($data);
        $this->enforceSubmittedScope($data);

        $user = new User();
        $this->fill($user, $data, true);
        $user->created_by = (int) session('hr_user_id');
        $user->hr_signup_date = now()->format('Y-m-d H:i:s');
        $user->save();
        $this->repository->replaceUserBranches((int) $user->hr_id, $data['branch_ids']);
        $this->repository->replaceDirectPermissions((int) $user->hr_id, $data['permissions'] ?? []);

        return $user;
    }

    public function update(User $user, array $data): void
    {
        $data = $this->normalizeBranchData($data);
        $this->enforceSubmittedScope($data);

        if ((int) $user->hr_id === (int) session('hr_user_id')) {
            foreach (['hr_user_level', 'companies_groups_id', 'activated', 'groupid'] as $field) {
                if ((string) ($data[$field] ?? '') !== (string) $user->{$field}) {
                    throw ValidationException::withMessages([$field => __('system_administration.users.errors.self_security_change')]);
                }
            }
            if ($data['branch_ids'] !== $this->repository->branchIds((int) $user->hr_id, (int) $user->branch_id)) {
                throw ValidationException::withMessages(['branch_ids' => __('system_administration.users.errors.self_security_change')]);
            }
            $existingPermissions = $this->repository->directPermissions((int) $user->hr_id)->sort()->values()->all();
            $submittedPermissions = collect($data['permissions'] ?? [])->unique()->sort()->values()->all();
            if ($existingPermissions !== $submittedPermissions) {
                throw ValidationException::withMessages(['permissions' => __('system_administration.users.errors.self_security_change')]);
            }
        }

        if ((int) $user->hr_user_level === 3
            && ((int) $data['hr_user_level'] !== 3 || (string) $data['activated'] !== '1')
            && $this->repository->activeSuperAdministratorCount() <= 1) {
            throw ValidationException::withMessages(['hr_user_level' => __('system_administration.users.errors.final_super_admin')]);
        }

        $this->fill($user, $data, false);
        $user->updated_by = (int) session('hr_user_id');
        $user->save();
        $this->repository->replaceUserBranches((int) $user->hr_id, $data['branch_ids']);

        if ((int) $user->hr_id !== (int) session('hr_user_id')) {
            $this->repository->replaceDirectPermissions((int) $user->hr_id, $data['permissions'] ?? []);
        }
    }

    private function enforceSubmittedScope(array $data): void
    {
        $catalog = $this->repository->permissionCatalog()->pluck('code')->all();
        $submitted = array_values(array_unique($data['permissions'] ?? []));
        $branchIds = $data['branch_ids'];

        if (array_diff($submitted, $catalog) !== []) {
            throw ValidationException::withMessages(['permissions' => __('system_administration.users.errors.invalid_permission')]);
        }

        $validBranchCount = DB::table('branches')
            ->whereIn('id', $branchIds)
            ->where('companies_groups_id', (int) $data['companies_groups_id'])
            ->count();
        $validBranch = $validBranchCount === count($branchIds);
        $validGroup = (int) $data['groupid'] === 0
            || DB::table('user_groups')->where('id', (int) $data['groupid'])->where('publish', 1)->exists();
        if (! $validBranch || ! $validGroup) {
            throw ValidationException::withMessages(['branch_ids' => __('system_administration.users.errors.out_of_scope')]);
        }

        if ($this->isSuperAdministrator()) {
            return;
        }

        if ((int) $data['companies_groups_id'] !== (int) session('companies_groups_id')
            || $branchIds !== [(int) session('hr_branch_id')]
            || (int) $data['hr_user_level'] === 3) {
            abort(403, __('system_administration.users.errors.out_of_scope'));
        }

        $ownPermissions = (array) session('hm_permissions', []);
        if (array_diff($submitted, $ownPermissions) !== []) {
            abort(403, __('system_administration.users.errors.privilege_escalation'));
        }
        $inherited = $this->repository->inheritedPermissions((int) $data['groupid'])->unique()->all();
        if (array_diff($inherited, $ownPermissions) !== []) {
            abort(403, __('system_administration.users.errors.privilege_escalation'));
        }
    }

    private function fill(User $user, array $data, bool $creating): void
    {
        $user->fill(collect($data)->only([
            'hr_first_name', 'hr_last_name', 'hr_email_address', 'hr_username', 'hr_user_level',
            'branch_id', 'companies_groups_id', 'groupid', 'mobile', 'activated',
        ])->all());

        if ($creating || ! empty($data['password'])) {
            $user->hr_password = hash('sha256', (string) $data['password']);
            $user->lastPassChange = now()->format('Y-m-d H:i:s');
        }
    }

    /**
     * Normalize the new multi-branch payload while accepting branch_id from
     * older clients and existing integrations.
     *
     * @return array<string, mixed>
     */
    private function normalizeBranchData(array $data): array
    {
        $branchIds = $data['branch_ids'] ?? [$data['branch_id'] ?? 0];
        $branchIds = collect(is_array($branchIds) ? $branchIds : [$branchIds])
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $data['branch_ids'] = $branchIds;
        $data['branch_id'] = $branchIds[0] ?? 0;

        return $data;
    }
}
