<?php

namespace Tests\Feature;

use App\Data\NavigationItem;
use App\Services\Dashboard\NavigationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyNavigationParityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['hm.permissions.bypass' => false, 'hm.permissions.admin_levels' => [3]]);

        foreach (['user_permission', 'user_groups_permission', 'user_role', 'role_perm', 'permissions'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('user_permission', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('userid');
            $table->unsignedInteger('pageid')->default(0);
            $table->string('page');
            $table->string('permit');
        });
        Schema::create('user_groups_permission', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('groupid');
            $table->string('page');
            $table->string('permit');
        });
        Schema::create('user_role', function (Blueprint $table): void {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('role_id');
        });
        Schema::create('role_perm', function (Blueprint $table): void {
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('perm_id');
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->unsignedInteger('perm_id')->primary();
            $table->string('perm_desc');
        });
    }

    public function test_branch_one_roles_receive_all_permitted_legacy_workflow_groups(): void
    {
        $routes = $this->routesFor($this->auditSession(10, 2, 1));

        $this->assertContains('modules.emergency-reception.guide', $routes);
        $this->assertContains('modules.emergency-reception.index', $routes);
        $this->assertContains('modules.medical-referrals.index', $routes);
        $this->assertContains('modules.medical-agreements.index', $routes);
        $this->assertContains('modules.governmental-services.index', $routes);
        $this->assertContains('modules.legacy-office.holidays.index', $routes);
        $this->assertContains('modules.legacy-office.medical-reports.index', $routes);
        $this->assertContains('modules.legacy-office.memos.index', $routes);
        $this->assertContains('modules.legacy-office.memos.received', $routes);
        $this->assertContains('modules.legacy-office.coverage.index', $routes);
        $this->assertContains('modules.outgoing-correspondence.index', $routes);
        $this->assertContains('modules.work-absence.notifications.index', $routes);
        $this->assertContains('modules.work-absence.dashboard', $routes);
        $this->assertContains('modules.training.management.index', $routes);
        $this->assertContains('modules.training.coordination.index', $routes);
        $this->assertContains('modules.technical-failures.index', $routes);

        $titles = $this->titlesFor($this->auditSession(10, 2, 1));
        $this->assertContains(__('dashboard.nav.agreement_sadq'), $titles);
        $this->assertContains(__('dashboard.nav.agreement_sadq_manual'), $titles);
    }

    public function test_branch_two_role_only_receives_its_legacy_memo_workflows(): void
    {
        $routes = $this->routesFor($this->auditSession(20, 4, 2));

        $this->assertContains('modules.legacy-office.memos.index', $routes);
        $this->assertContains('modules.legacy-office.memos.received', $routes);
        $this->assertContains('modules.legacy-office.coverage.index', $routes);
        $this->assertContains('modules.outgoing-correspondence.index', $routes);
        $this->assertContains('modules.work-absence.notifications.index', $routes);
        $this->assertContains('modules.work-absence.dashboard', $routes);
        $this->assertContains('modules.training.management.index', $routes);
        $this->assertContains('modules.training.coordination.index', $routes);
        $this->assertNotContains('modules.technical-failures.index', $routes);
        $this->assertNotContains('modules.medical-agreements.index', $routes);
        $this->assertNotContains('modules.emergency-reception.index', $routes);
        $this->assertNotContains('modules.medical-referrals.index', $routes);
        $this->assertNotContains('modules.medical-agreements.index', $routes);
        $this->assertNotContains('modules.governmental-services.index', $routes);
        $this->assertNotContains('modules.legacy-office.holidays.index', $routes);
    }

    public function test_sadq_links_require_the_same_legacy_privileges_as_the_pages(): void
    {
        DB::table('permissions')->insert([
            ['perm_id' => 6, 'perm_desc' => 'Medical Services Provision Agreement non Yaqeen'],
            ['perm_id' => 7, 'perm_desc' => 'Medical Services Provision Agreement Yaqeen'],
        ]);
        DB::table('user_role')->insert(['user_id' => 30, 'role_id' => 3]);
        DB::table('role_perm')->insert([['role_id' => 3, 'perm_id' => 6], ['role_id' => 3, 'perm_id' => 7]]);

        $titles = $this->titlesFor($this->auditSession(30, 1, 1));

        $this->assertContains(__('dashboard.nav.agreement_sadq'), $titles);
        $this->assertContains(__('dashboard.nav.agreement_sadq_manual'), $titles);
    }

    public function test_super_admin_does_not_receive_branch_shell_only_legacy_groups(): void
    {
        $routes = $this->routesFor($this->auditSession(40, 3, 1));

        $this->assertNotContains('modules.emergency-reception.index', $routes);
        $this->assertNotContains('modules.medical-referrals.index', $routes);
        $this->assertNotContains('modules.medical-agreements.index', $routes);
        $this->assertNotContains('modules.governmental-services.index', $routes);
        $this->assertNotContains('modules.legacy-office.memos.index', $routes);
    }

    public function test_all_audit_roles_can_reach_the_dashboard_from_the_sidebar(): void
    {
        foreach ([3, 2, 1, 4] as $level) {
            $this->assertContains(
                route('dashboard'),
                $this->urlsFor($this->auditSession(100 + $level, $level, $level === 4 ? 2 : 1)),
            );
        }
    }

    public function test_dashboard_is_not_selected_as_the_authenticated_landing_route_for_any_audit_role(): void
    {
        foreach ([3, 2, 1, 4] as $level) {
            $this->startSession();
            session()->flush();
            session($this->auditSession(400 + $level, $level, $level === 4 ? 2 : 1));

            $this->assertNotSame('dashboard', app(NavigationService::class)->homeRouteName());
        }
    }

    public function test_super_admin_reference_pages_are_visible_and_branch_roles_do_not_receive_them(): void
    {
        $types = [
            'complaint-closing-reasons',
            'complaint-letter-receivers',
            'post-types',
            'job-titles',
            'governmental-services',
            'companies',
            'branches',
            'departments',
            'needs',
            'work-areas',
            'inquiries',
            'service-types',
        ];
        $superUrls = $this->urlsFor($this->auditSession(40, 3, 1));
        $branchUrls = $this->urlsFor($this->auditSession(10, 2, 1));

        foreach ($types as $type) {
            $url = route('modules.system-admin.reference.index', ['type' => $type]);
            $this->assertContains($url, $superUrls, $type.' must be visible to the super admin');
            $this->assertNotContains($url, $branchUrls, $type.' must remain hidden from branch roles');

            $middleware = app('router')->getRoutes()->getByName('modules.system-admin.reference.index')->gatherMiddleware();
            $this->assertContains('admin', $middleware);
        }
    }

    public function test_branch_roles_can_reach_each_old_location_detail_from_the_sidebar(): void
    {
        $targets = [
            ...array_map(
                fn (int $id): string => route('modules.service-locations.show', ['outpatientClinic' => $id]),
                range(1, 7),
            ),
            route('modules.service-locations.floors'),
            ...array_map(
                fn (int $id): string => route('modules.service-locations.floors.show', ['floor' => $id]),
                range(1, 8),
            ),
        ];

        foreach ([2, 1, 4] as $level) {
            $urls = $this->urlsFor($this->auditSession(200 + $level, $level, $level === 4 ? 2 : 1));

            foreach ($targets as $target) {
                $this->assertContains($target, $urls, 'Missing branch location target for level '.$level.': '.$target);
            }
        }

        $superUrls = $this->urlsFor($this->auditSession(40, 3, 1));
        $this->assertNotContains($targets[0], $superUrls);
    }

    public function test_sidebar_has_no_duplicate_visible_urls_for_any_audit_role(): void
    {
        foreach ([3, 2, 1, 4] as $level) {
            $urls = $this->urlsFor($this->auditSession(300 + $level, $level, $level === 4 ? 2 : 1));

            $this->assertCount(count(array_unique($urls)), $urls, 'Duplicate sidebar URL for level '.$level);
        }
    }

    public function test_active_reference_child_opens_the_system_administration_group(): void
    {
        $this->startSession();
        session()->flush();
        session($this->auditSession(40, 3, 1));

        $route = app('router')->getRoutes()->getByName('modules.system-admin.reference.index');
        $request = Request::create(route('modules.system-admin.reference.index', ['type' => 'post-types']), 'GET');
        $route->bind($request);
        $request->setRouteResolver(static fn () => $route);
        $this->app->instance('request', $request);
        $currentRoute = new \ReflectionProperty(app('router'), 'current');
        $currentRoute->setValue(app('router'), $route);

        $system = collect(app(NavigationService::class)->sidebar())
            ->first(fn (NavigationItem $item): bool => $item->collapseId === 'sidebar-system-administration');
        $this->assertNotNull($system);

        $child = collect($system->children)
            ->first(fn (NavigationItem $item): bool => $item->url === route('modules.system-admin.reference.index', ['type' => 'post-types']));

        $this->assertNotNull($child);
        $this->assertTrue($child->active);
        $this->assertTrue($system->active);
    }

    /** @return array<string, int|bool> */
    private function auditSession(int $userId, int $level, int $branch): array
    {
        return [
            'authenticated' => true,
            'hr_user_id' => $userId,
            'hr_user_level' => $level,
            'hr_branch_id' => $branch,
            'companies_groups_id' => 1,
            'groupid' => 0,
        ];
    }

    /** @param array<string, int|bool> $session
     * @return list<string>
     */
    private function routesFor(array $session): array
    {
        $this->startSession();
        session()->flush();
        session($session);

        return $this->flatten(app(NavigationService::class)->sidebar())
            ->pluck('route')->filter()->values()->all();
    }

    /** @param array<string, int|bool> $session
     * @return list<string>
     */
    private function urlsFor(array $session): array
    {
        $this->startSession();
        session()->flush();
        session($session);

        return $this->flatten(app(NavigationService::class)->sidebar())
            ->pluck('url')->values()->all();
    }

    /** @param array<string, int|bool> $session
     * @return list<string>
     */
    private function titlesFor(array $session): array
    {
        $this->startSession();
        session()->flush();
        session($session);

        return $this->flatten(app(NavigationService::class)->sidebar())
            ->pluck('title')->values()->all();
    }

    /** @param list<NavigationItem> $items */
    private function flatten(array $items)
    {
        return collect($items)->flatMap(fn (NavigationItem $item) => [$item, ...$this->flatten($item->children)]);
    }
}
