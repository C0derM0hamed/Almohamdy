<?php

namespace App\Services\Auth;

use App\Repositories\Auth\PermissionRepository;
use Illuminate\Auth\Access\AuthorizationException;

class PermissionService
{
    public const MANAGE_USERS = 'users';

    public const MANAGE_PERMISSIONS = 'user_groups_permissins';

    public function __construct(
        private readonly PermissionRepository $repository,
        private readonly LegacyScopeService $legacyScopes,
    ) {}

    public function can(string $permission): bool
    {
        if (config('hm.permissions.bypass', false)) {
            return true;
        }

        if ($this->hasAdminGrantAll()) {
            return true;
        }

        return in_array($permission, $this->sessionPermissions(), true)
            || $this->legacyScopes->allowsPermission($permission);
    }

    public function isAdmin(): bool
    {
        if (config('hm.permissions.bypass', false)) {
            return true;
        }

        return $this->hasAdminGrantAll();
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin()
            || ($this->can(self::MANAGE_USERS) && $this->can(self::MANAGE_PERMISSIONS));
    }

    public function authorize(string $permission): void
    {
        if ($this->can($permission)) {
            return;
        }

        throw new AuthorizationException($this->messageFor($permission));
    }

    public function refreshSessionPermissions(int $userId, int $groupId): void
    {
        session([
            'hm_permissions' => $this->repository->permissionsForUser($userId, $groupId),
        ]);
    }

    /**
     * @return list<string>
     */
    private function sessionPermissions(): array
    {
        $permissions = session('hm_permissions');

        if ($permissions === null) {
            $userId = (int) session('hr_user_id', 0);
            $groupId = (int) session('groupid', 0);

            if ($userId > 0) {
                $this->refreshSessionPermissions($userId, $groupId);
                $permissions = session('hm_permissions', []);
            }
        }

        return is_array($permissions) ? array_values($permissions) : [];
    }

    private function hasAdminGrantAll(): bool
    {
        $levels = config('hm.permissions.admin_levels', []);

        return in_array((int) session('hr_user_level'), $levels, true);
    }

    private function messageFor(string $permission): string
    {
        foreach (['work_absence_notification', 'employee_leave'] as $namespace) {
            $key = $namespace.'.errors.permission.'.$permission;

            if (__($key) !== $key) {
                return __($key);
            }
        }

        $fallbackKey = 'employee_leave.errors.unauthorized';
        if (__($fallbackKey) !== $fallbackKey) {
            return __($fallbackKey);
        }

        return __('work_absence_notification.errors.unauthorized');
    }
}
