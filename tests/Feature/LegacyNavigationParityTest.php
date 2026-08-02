<?php

namespace Tests\Feature;

use App\Data\NavigationItem;
use App\Services\Dashboard\NavigationService;
use Illuminate\Database\Schema\Blueprint;
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

        $titles = $this->titlesFor($this->auditSession(10, 2, 1));
        $this->assertNotContains(__('dashboard.nav.agreement_sadq'), $titles);
        $this->assertNotContains(__('dashboard.nav.agreement_sadq_manual'), $titles);
    }

    public function test_branch_two_role_only_receives_its_legacy_memo_workflows(): void
    {
        $routes = $this->routesFor($this->auditSession(20, 4, 2));

        $this->assertContains('modules.legacy-office.memos.index', $routes);
        $this->assertContains('modules.legacy-office.memos.received', $routes);
        $this->assertContains('modules.legacy-office.coverage.index', $routes);
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
