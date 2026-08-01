<?php

namespace App\Services\Dashboard;

use App\Data\NavigationItem;
use App\Services\Auth\PermissionService;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class NavigationService
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    public function homeRouteName(): string
    {
        $items = $this->sidebar();

        foreach ($items as $item) {
            if ($item->hasChildren()) {
                foreach ($item->children as $child) {
                    if ($child->route !== null && $child->route !== '') {
                        return $child->route;
                    }
                }

                continue;
            }

            if ($item->route !== null && $item->route !== '') {
                return $item->route;
            }
        }

        return 'login';
    }

    /**
     * @return list<NavigationItem>
     */
    public function sidebar(): array
    {
        $currentRoute = Route::currentRouteName();
        $items = [];

        foreach (config('hm.navigation.sidebar', []) as $item) {
            $navItem = $this->resolveSidebarItem($item, $currentRoute);

            if ($navItem !== null) {
                $items[] = $navItem;
            }
        }

        return $items;
    }

    /**
     * @return array{heading: string, subheading: string}|null
     */
    public function activeSidebarContext(): ?array
    {
        $currentRoute = Route::currentRouteName();

        foreach (config('hm.navigation.sidebar', []) as $item) {
            $type = (string) ($item['type'] ?? 'route');

            if ($type === 'group') {
                foreach ($item['children'] ?? [] as $child) {
                    if (! $this->itemMatchesRoute($child, $currentRoute)) {
                        continue;
                    }

                    return $this->contextFromConfigItem($child);
                }

                continue;
            }

            if (! $this->itemMatchesRoute($item, $currentRoute)) {
                continue;
            }

            return $this->contextFromConfigItem($item);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{heading: string, subheading: string}|null
     */
    private function contextFromConfigItem(array $item): ?array
    {
        $labelKey = (string) ($item['label_key'] ?? '');
        $subtitleKey = (string) ($item['subtitle_key'] ?? '');

        if ($labelKey === '') {
            return null;
        }

        return [
            'heading' => __('dashboard.nav.'.$labelKey),
            'subheading' => $subtitleKey !== '' ? __($subtitleKey) : '',
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function itemMatchesRoute(array $item, ?string $currentRoute): bool
    {
        $routeName = (string) ($item['route'] ?? '');

        if ($routeName === '' || ! Route::has($routeName)) {
            return false;
        }

        if ($this->requiresWorkAbsenceView($routeName)
            && ! $this->permissions->can(WorkAbsenceNotificationPermissions::VIEW)) {
            return false;
        }

        if (! $this->hasConfiguredPermission($item)) {
            return false;
        }

        if (! $this->passesScopeRestrictions($item)) {
            return false;
        }

        if (! empty($item['admin_only']) && ! $this->permissions->isAdmin()) {
            return false;
        }

        if (! empty($item['permission_admin_only']) && ! $this->permissions->canManageUsers()) {
            return false;
        }

        $routeParams = $item['route_params'] ?? [];

        if (is_array($routeParams) && $routeParams !== []) {
            if ($currentRoute !== $routeName) {
                return false;
            }

            $currentParams = Route::current()?->parameters() ?? [];

            foreach ($routeParams as $key => $value) {
                if ((string) ($currentParams[$key] ?? '') !== (string) $value) {
                    return false;
                }
            }

            return true;
        }

        $prefixes = $item['active_prefixes'] ?? [(string) ($item['active_prefix'] ?? '')];

        foreach ($prefixes as $prefix) {
            if ($this->routeMatchesPrefix($currentRoute, (string) $prefix)
                || $this->routeMatchesPrefix($currentRoute, $routeName)) {
                return true;
            }
        }

        $activeParams = $item['active_params'] ?? null;

        if (is_array($activeParams) && $activeParams !== [] && $currentRoute !== null) {
            $currentParams = Route::current()?->parameters() ?? [];
            $paramsMatch = true;

            foreach ($activeParams as $key => $value) {
                if ((string) ($currentParams[$key] ?? '') !== (string) $value) {
                    $paramsMatch = false;
                    break;
                }
            }

            if ($paramsMatch && str_starts_with($currentRoute, 'modules.inquiries.')) {
                return true;
            }
        }

        return $currentRoute === $routeName;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveSidebarItem(array $item, ?string $currentRoute): ?NavigationItem
    {
        $type = (string) ($item['type'] ?? 'route');

        if ($type === 'home') {
            return null;
        }

        if ($type === 'group') {
            return $this->resolveGroupItem($item, $currentRoute);
        }

        $labelKey = (string) ($item['label_key'] ?? '');
        $icon = (string) ($item['icon'] ?? 'bi-circle');
        $routeName = (string) ($item['route'] ?? '');

        if ($routeName === '' || ! Route::has($routeName)) {
            return null;
        }

        if ($this->requiresWorkAbsenceView($routeName)
            && ! $this->permissions->can(WorkAbsenceNotificationPermissions::VIEW)) {
            return null;
        }

        if (! $this->hasConfiguredPermission($item)) {
            return null;
        }

        if (! $this->passesScopeRestrictions($item)) {
            return null;
        }

        if (! empty($item['admin_only']) && ! $this->permissions->isAdmin()) {
            return null;
        }

        if (! empty($item['permission_admin_only']) && ! $this->permissions->canManageUsers()) {
            return null;
        }

        $routeParams = is_array($item['route_params'] ?? null) ? $item['route_params'] : [];
        $isActive = $this->itemMatchesRoute($item, $currentRoute);

        return new NavigationItem(
            title: __('dashboard.nav.'.$labelKey),
            url: route($routeName, $routeParams),
            icon: $icon,
            route: $routeName,
            active: $isActive,
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveGroupItem(array $item, ?string $currentRoute): ?NavigationItem
    {
        $labelKey = (string) ($item['label_key'] ?? '');
        $icon = (string) ($item['icon'] ?? 'bi-folder');
        $childrenConfig = $item['children'] ?? [];

        if ($labelKey === '' || ! is_array($childrenConfig) || $childrenConfig === []) {
            return null;
        }

        if (! empty($item['admin_only']) && ! $this->permissions->isAdmin()) {
            return null;
        }

        if (! empty($item['permission_admin_only']) && ! $this->permissions->canManageUsers()) {
            return null;
        }

        $children = [];
        $groupActive = false;

        foreach ($childrenConfig as $child) {
            if (! is_array($child)) {
                continue;
            }

            $resolved = $this->resolveSidebarItem($child, $currentRoute);

            if ($resolved === null) {
                continue;
            }

            $children[] = $resolved;

            if ($resolved->active) {
                $groupActive = true;
            }
        }

        if ($children === []) {
            return null;
        }

        $collapseId = 'sidebar-'.Str::slug($labelKey, '-');

        return new NavigationItem(
            title: __('dashboard.nav.'.$labelKey),
            url: '#'.$collapseId,
            icon: $icon,
            route: null,
            active: $groupActive,
            children: $children,
            isGroup: true,
            collapseId: $collapseId,
        );
    }

    private function routeMatchesPrefix(?string $currentRoute, string $prefix): bool
    {
        if ($currentRoute === null || $prefix === '') {
            return false;
        }

        if ($currentRoute === $prefix) {
            return true;
        }

        $segmentPrefix = rtrim($prefix, '.').'.';

        return str_starts_with($currentRoute, $segmentPrefix);
    }

    private function requiresWorkAbsenceView(string $routeName): bool
    {
        return str_starts_with($routeName, 'modules.work-absence.');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function hasConfiguredPermission(array $item): bool
    {
        $permission = trim((string) ($item['permission'] ?? ''));

        return $permission === '' || $this->permissions->can($permission);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function passesScopeRestrictions(array $item): bool
    {
        $allowedCompanies = array_map('intval', is_array($item['company_ids'] ?? null) ? $item['company_ids'] : []);
        $allowedBranches = array_map('intval', is_array($item['branch_ids'] ?? null) ? $item['branch_ids'] : []);

        if ($allowedCompanies !== [] && ! in_array((int) session('companies_groups_id'), $allowedCompanies, true)) {
            return false;
        }

        if ($allowedBranches !== [] && ! in_array((int) session('hr_branch_id'), $allowedBranches, true)) {
            return false;
        }

        return true;
    }

    /**
     * @return list<array{title: string, time: string}>
     */
    public function notifications(): array
    {
        if (! config('hm.notifications.enabled', false)) {
            return [];
        }

        return config('hm.notifications.items', []);
    }

    public function notificationCount(): int
    {
        return count($this->notifications());
    }

    public function userDisplayName(): string
    {
        return trim(session('hr_first_name', '').' '.session('hr_last_name', ''));
    }

    public function userInitials(): string
    {
        $first = mb_substr((string) session('hr_first_name', ''), 0, 1);
        $last = mb_substr((string) session('hr_last_name', ''), 0, 1);

        $initials = trim($first.$last);

        return $initials !== '' ? mb_strtoupper($initials) : 'U';
    }
}
