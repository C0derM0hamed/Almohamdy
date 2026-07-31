<?php

namespace App\Services\SystemAdministration;

use App\Models\User;
use App\Repositories\SystemAdministration\UserPermissionAdminRepository;
use App\Services\Auth\PermissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserPermissionAdminService
{
    public function __construct(
        private readonly UserPermissionAdminRepository $repository,
        private readonly PermissionService $permissions,
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

        return [
            'user' => $user,
            'groups' => $this->repository->groups(),
            'branches' => $this->repository->branches($this->isSuperAdministrator() ? null : (int) session('companies_groups_id')),
            'companies' => $this->isSuperAdministrator() ? $this->repository->companies() : collect(),
            'permissionCatalog' => $this->repository->permissionCatalog(),
            'directPermissions' => $direct,
            'inheritedPermissions' => $inherited,
            'effectivePermissions' => $direct->merge($inherited)->unique()->sort()->values(),
            'isSuperAdministrator' => $this->isSuperAdministrator(),
        ];
    }

    public function create(array $data): User
    {
        $this->enforceSubmittedScope($data);

        $user = new User();
        $this->fill($user, $data, true);
        $user->created_by = (int) session('hr_user_id');
        $user->hr_signup_date = now()->format('Y-m-d H:i:s');
        $user->save();
        $this->repository->replaceDirectPermissions((int) $user->hr_id, $data['permissions'] ?? []);

        return $user;
    }

    public function update(User $user, array $data): void
    {
        $this->enforceSubmittedScope($data);

        if ((int) $user->hr_id === (int) session('hr_user_id')) {
            foreach (['hr_user_level', 'branch_id', 'companies_groups_id', 'activated', 'groupid'] as $field) {
                if ((string) ($data[$field] ?? '') !== (string) $user->{$field}) {
                    throw ValidationException::withMessages([$field => __('system_administration.users.errors.self_security_change')]);
                }
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

        if ((int) $user->hr_id !== (int) session('hr_user_id')) {
            $this->repository->replaceDirectPermissions((int) $user->hr_id, $data['permissions'] ?? []);
        }
    }

    private function enforceSubmittedScope(array $data): void
    {
        $catalog = $this->repository->permissionCatalog()->pluck('page')->all();
        $submitted = array_values(array_unique($data['permissions'] ?? []));

        if (array_diff($submitted, $catalog) !== []) {
            throw ValidationException::withMessages(['permissions' => __('system_administration.users.errors.invalid_permission')]);
        }

        $validBranch = DB::table('branches')->where('id', (int) $data['branch_id'])
            ->where('companies_groups_id', (int) $data['companies_groups_id'])->exists();
        $validGroup = (int) $data['groupid'] === 0
            || DB::table('user_groups')->where('id', (int) $data['groupid'])->where('publish', 1)->exists();
        if (! $validBranch || ! $validGroup) {
            throw ValidationException::withMessages(['branch_id' => __('system_administration.users.errors.out_of_scope')]);
        }

        if ($this->isSuperAdministrator()) {
            return;
        }

        if ((int) $data['companies_groups_id'] !== (int) session('companies_groups_id')
            || (int) $data['branch_id'] !== (int) session('hr_branch_id')
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
}
