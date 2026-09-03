<?php

namespace App\Services\Auth;

use App\Repositories\Auth\PermissionRepository;
use Illuminate\Auth\Access\AuthorizationException;

class PermissionService
{
    public const MANAGE_USERS = 'users';

    public const MANAGE_PERMISSIONS = 'user_groups_permissins';

    /** @var array<string, array{decision: string, source: string}>|null */
    private ?array $requestPermissionStates = null;

    public function __construct(
        private readonly PermissionRepository $repository,
        private readonly LegacyScopeService $legacyScopes,
        private readonly PermissionRegistry $registry,
    ) {}

    public function can(string $permission): bool
    {
        return $this->decision($permission) === PermissionDecision::Allow;
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
        $states = $this->repository->permissionStatesForUser($userId, $groupId);
        $this->requestPermissionStates = $states;
        session([
            'hm_permissions' => collect($states)->filter(fn (array $state): bool => $state['decision'] === 'allow')->keys()->values()->all(),
            'hm_permission_states' => $states,
        ]);
    }

    public function decision(string $permission): PermissionDecision
    {
        if (config('hm.permissions.bypass', false) || $this->hasAdminGrantAll()) {
            return PermissionDecision::Allow;
        }

        $states = $this->sessionPermissionStates();
        $hasAllow = false;
        foreach ($this->registry->storageCodes($permission) as $code) {
            $state = $states[$code]['decision'] ?? 'none';
            if ($state === 'deny') {
                return PermissionDecision::Deny;
            }
            $hasAllow = $hasAllow || $state === 'allow';
        }

        if ($hasAllow) {
            return PermissionDecision::Allow;
        }

        // Compatibility mode preserves the existing implicit scopes until the
        // backfill command has materialised them as explicit grants.
        if (config('hm.permissions.enforcement_mode', 'compat') === 'compat'
            && $this->legacyScopes->allowsPermission($permission)) {
            return PermissionDecision::Allow;
        }

        return PermissionDecision::None;
    }

    /** @return list<string> */
    public function effectivePermissions(): array
    {
        return $this->sessionPermissions();
    }

    /**
     * @return list<string>
     */
    private function sessionPermissions(): array
    {
        $states = $this->sessionPermissionStates();

        return collect($states)
            ->filter(fn (array $state): bool => $state['decision'] === 'allow')
            ->keys()
            ->values()
            ->all();
    }

    /** @return array<string, array{decision: string, source: string}> */
    private function currentPermissionStates(): array
    {
        if ($this->requestPermissionStates !== null) {
            return $this->requestPermissionStates;
        }

        $userId = (int) session('hr_user_id', 0);
        $groupId = (int) session('groupid', 0);

        if ($userId > 0) {
            // Reload on the first permission check of each request. This makes
            // a grant or revoke effective on the next request even when the
            // session still contains the previous snapshot.
            $this->refreshSessionPermissions($userId, $groupId);
        }

        return $this->requestPermissionStates ?? [];
    }

    /** @return array<string, array{decision: string, source: string}> */
    private function sessionPermissionStates(): array
    {
        return $this->currentPermissionStates();
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
