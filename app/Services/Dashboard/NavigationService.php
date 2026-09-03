<?php

namespace App\Services\Dashboard;

use App\Data\NavigationItem;
use App\Services\Auth\PermissionService;
use App\Services\Auth\PermissionRegistry;
use App\Services\Auth\LegacyScopeService;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NavigationService
{
    public function __construct(
        private readonly PermissionService $permissions,
        private readonly LegacyScopeService $legacyScopes,
        private readonly PermissionRegistry $registry,
    ) {}

    /** @return array{route: string, params: array<string, mixed>} */
    public function homeLanding(): array
    {
        foreach ($this->sidebar() as $item) {
            $landing = $this->firstLeafLanding($item);
            if ($landing !== null) {
                return $landing;
            }
        }

        return ['route' => 'login', 'params' => []];
    }

    public function homeRouteName(): string
    {
        return $this->homeLanding()['route'];
    }

    /** @return array<string, mixed> */
    public function homeRouteParams(): array
    {
        return $this->homeLanding()['params'];
    }

    public function homeUrl(): string
    {
        $landing = $this->homeLanding();

        return route($landing['route'], $landing['params'], false);
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

        // A level-3 account is the system-wide administrator. It receives
        // the canonical entry point for every branch/company workflow, but
        // legacy menus contain aliases of the same target under different
        // branch headings. Keep the first canonical occurrence so the global
        // menu is complete without presenting duplicate actions.
        return $this->permissions->isAdmin()
            ? $this->uniqueNavigationItems($items)
            : $items;
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
                $context = $this->activeContextInChildren($item['children'] ?? [], $currentRoute);
                if ($context !== null) {
                    return $context;
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
        $excludedPrefixes = $item['active_exclude_prefixes'] ?? [];

        if ($currentRoute !== null && is_array($excludedPrefixes)) {
            foreach ($excludedPrefixes as $excludedPrefix) {
                if ($this->routeMatchesPrefix($currentRoute, (string) $excludedPrefix)) {
                    return false;
                }
            }
        }

        $routeName = (string) ($item['route'] ?? '');

        if ($routeName === '' || ! Route::has($routeName)) {
            return false;
        }

        if ($this->requiresWorkAbsenceView($routeName)
            && ! $this->permissions->can(WorkAbsenceNotificationPermissions::VIEW)
            && ! $this->legacyScopes->allows(LegacyScopeService::EMPLOYEE_SERVICES)) {
            return false;
        }

        if (! $this->hasConfiguredPermission($item)) {
            return false;
        }

        if (! $this->passesScopeRestrictions($item)) {
            return false;
        }

        if (! $this->hasLegacyPrivilege($item)) {
            return false;
        }

        if (! empty($item['admin_only']) && ! $this->permissions->isAdmin()) {
            return false;
        }

        if (! empty($item['permission_admin_only']) && ! $this->permissions->canManageUsers()) {
            return false;
        }

        $activeRoutePatterns = $item['active_route_patterns'] ?? [];
        if ($currentRoute !== null && is_array($activeRoutePatterns) && $activeRoutePatterns !== []) {
            return collect($activeRoutePatterns)
                ->contains(fn ($pattern): bool => Str::is((string) $pattern, $currentRoute));
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

        if (! $this->publishedServiceSection($item)) {
            return null;
        }

        if ($this->requiresWorkAbsenceView($routeName)
            && ! $this->permissions->can(WorkAbsenceNotificationPermissions::VIEW)
            && ! $this->legacyScopes->allows(LegacyScopeService::EMPLOYEE_SERVICES)) {
            return null;
        }

        if (! $this->hasConfiguredPermission($item)) {
            return null;
        }

        if (! $this->passesScopeRestrictions($item)) {
            return null;
        }

        if (! $this->hasLegacyPrivilege($item)) {
            return null;
        }

        if (! empty($item['admin_only']) && ! $this->permissions->isAdmin()) {
            return null;
        }

        if (! empty($item['permission_admin_only']) && ! $this->permissions->canManageUsers()) {
            return null;
        }

        if (! $this->passesScopeRestrictions($item) || ! $this->hasLegacyPrivilege($item)) {
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
            routeParams: $routeParams,
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

    /** @param list<NavigationItem> $children
     * @return array{route: string, params: array<string, mixed>}|null
     */
    private function firstLeafLanding(NavigationItem $item): ?array
    {
        if ($item->route === 'dashboard') {
            return null;
        }

        if ($item->hasChildren()) {
            foreach ($item->children as $child) {
                $landing = $this->firstLeafLanding($child);
                if ($landing !== null) {
                    return $landing;
                }
            }

            return null;
        }

        if ($item->route === null || $item->route === '') {
            return null;
        }

        return ['route' => $item->route, 'params' => $item->routeParams];
    }

    /** @param list<NavigationItem> $children */
    private function firstLeafRoute(array $children): ?string
    {
        foreach ($children as $child) {
            if ($child->hasChildren()) {
                $route = $this->firstLeafRoute($child->children);
                if ($route !== null) {
                    return $route;
                }
                continue;
            }

            if ($child->route !== null && $child->route !== '') {
                return $child->route;
            }
        }

        return null;
    }

    /** @param mixed $children */
    private function activeContextInChildren(mixed $children, ?string $currentRoute): ?array
    {
        if (! is_array($children)) {
            return null;
        }

        foreach ($children as $child) {
            if (! is_array($child)) {
                continue;
            }

            if (($child['type'] ?? 'route') === 'group') {
                $nested = $this->activeContextInChildren($child['children'] ?? [], $currentRoute);
                if ($nested !== null) {
                    return $nested;
                }
                continue;
            }

            if ($this->itemMatchesRoute($child, $currentRoute)) {
                return $this->contextFromConfigItem($child);
            }
        }

        return null;
    }

    /** @param array<string, mixed> $item */
    private function publishedServiceSection(array $item): bool
    {
        $sectionId = (int) ($item['published_section_id'] ?? 0);
        if ($sectionId <= 0 || ! Schema::hasTable('services_sections') || ! Schema::hasColumn('services_sections', 'publish')) {
            return true;
        }

        return DB::table('services_sections')->where('id', $sectionId)->where('publish', 1)->exists();
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

        if ($permission !== '') {
            return $this->permissions->can($permission);
        }

        $routePermissions = $this->registry->codesForRoute((string) ($item['route'] ?? ''));
        if ($routePermissions !== []) {
            $allowed = collect($routePermissions)->contains(fn (string $code): bool => $this->permissions->can($code));
            if ($allowed) {
                return true;
            }

            if (config('hm.permissions.enforcement_mode', 'compat') === 'strict'
                || trim((string) ($item['permission'] ?? '')) !== '') {
                return false;
            }
        }

        $legacyScope = trim((string) ($item['legacy_scope'] ?? ''));

        if ($legacyScope === '') {
            return true;
        }

        return $this->legacyScopes->allows($legacyScope) || $this->hasLegacyPrivilege($item);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function passesScopeRestrictions(array $item): bool
    {
        $excludedCompanies = array_map('intval', is_array($item['excluded_company_ids'] ?? null) ? $item['excluded_company_ids'] : []);
        if ($excludedCompanies !== [] && in_array((int) session('companies_groups_id'), $excludedCompanies, true)) {
            return false;
        }

        $excludedBranches = array_map('intval', is_array($item['excluded_branch_ids'] ?? null) ? $item['excluded_branch_ids'] : []);
        if ($excludedBranches !== [] && in_array((int) session('hr_branch_id'), $excludedBranches, true)) {
            return false;
        }

        // Level 3 is the explicit global system administrator role. It is
        // allowed to navigate branch/company-specific modules; repository and
        // workflow authorization still protects each action. This intentionally
        // replaces the former email-based bypass, which made ordinary accounts
        // inherit unrelated branch menus.
        if ($this->permissions->isAdmin()) {
            return true;
        }

        $allowedCompanies = array_map('intval', is_array($item['company_ids'] ?? null) ? $item['company_ids'] : []);
        $allowedBranches = array_map('intval', is_array($item['branch_ids'] ?? null) ? $item['branch_ids'] : []);
        $allowedLevels = array_map('intval', is_array($item['user_levels'] ?? null) ? $item['user_levels'] : []);

        if ($allowedCompanies !== [] && ! in_array((int) session('companies_groups_id'), $allowedCompanies, true)) {
            return false;
        }

        if ($allowedBranches !== [] && ! in_array((int) session('hr_branch_id'), $allowedBranches, true)) {
            return false;
        }

        if ($allowedLevels !== [] && ! in_array((int) session('hr_user_level'), $allowedLevels, true)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function hasLegacyPrivilege(array $item): bool
    {
        if ($this->permissions->isAdmin()) {
            return true;
        }

        $legacyScope = trim((string) ($item['legacy_scope'] ?? ''));

        if ($legacyScope !== '' && $this->legacyScopes->allows($legacyScope)) {
            return true;
        }

        $privilege = trim((string) ($item['legacy_privilege'] ?? ''));

        if ($privilege === '') {
            return true;
        }

        if (! Schema::hasTable('user_role') || ! Schema::hasTable('role_perm') || ! Schema::hasTable('permissions')) {
            return false;
        }

        return DB::table('user_role as ur')
            ->join('role_perm as rp', 'rp.role_id', '=', 'ur.role_id')
            ->join('permissions as p', 'p.perm_id', '=', 'rp.perm_id')
            ->where('ur.user_id', (int) session('hr_user_id', 0))
            ->where('p.perm_desc', $privilege)
            ->exists();
    }

    /**
     * Collapse repeated legacy URLs for the global administrator while
     * retaining nested hierarchy and the first, canonical placement.
     *
     * @param list<NavigationItem> $items
     * @return list<NavigationItem>
     */
    private function uniqueNavigationItems(array $items): array
    {
        $seenUrls = [];

        return collect($items)
            ->map(function (NavigationItem $item) use (&$seenUrls): ?NavigationItem {
                return $this->uniqueNavigationItem($item, $seenUrls);
            })
            ->filter()
            ->values()
            ->all();
    }

    /** @param array<string, true> $seenUrls */
    private function uniqueNavigationItem(NavigationItem $item, array &$seenUrls): ?NavigationItem
    {
        if ($item->hasChildren()) {
            $children = collect($item->children)
                ->map(function (NavigationItem $child) use (&$seenUrls): ?NavigationItem {
                    return $this->uniqueNavigationItem($child, $seenUrls);
                })
                ->filter()
                ->values()
                ->all();

            if ($children === []) {
                return null;
            }

            return new NavigationItem(
                title: $item->title,
                url: $item->url,
                icon: $item->icon,
                route: $item->route,
                active: $item->active,
                children: $children,
                isGroup: $item->isGroup,
                collapseId: $item->collapseId,
            );
        }

        if (isset($seenUrls[$item->url])) {
            return null;
        }

        $seenUrls[$item->url] = true;

        return $item;
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
