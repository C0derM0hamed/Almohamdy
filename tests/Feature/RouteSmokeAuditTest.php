<?php

namespace Tests\Feature;

use App\Services\Dashboard\NavigationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RouteSmokeAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['hm.permissions.bypass' => false, 'hm.permissions.admin_levels' => [3]]);

        foreach (['user_permission', 'user_groups_permission'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('user_permission', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('userid');
            $table->integer('pageid')->default(0);
            $table->string('page');
            $table->string('permit');
        });
        Schema::create('user_groups_permission', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('groupid');
            $table->string('page');
            $table->string('permit');
        });
    }

    public function test_inquiries_are_hidden_from_sidebar_without_permission(): void
    {
        $this->startSession();
        session()->flush();
        session($this->branchSession());

        $routes = collect(app(NavigationService::class)->sidebar())
            ->flatMap(fn ($item) => $this->flattenRoutes($item))
            ->filter()
            ->values()
            ->all();

        $this->assertNotContains('modules.inquiries.outgoing.index', $routes);
        $this->assertNotContains('modules.inquiries.incoming.index', $routes);
    }

    public function test_consent_templates_are_admin_only_in_sidebar(): void
    {
        $this->startSession();
        session()->flush();
        session($this->branchSession());

        $routes = collect(app(NavigationService::class)->sidebar())
            ->flatMap(fn ($item) => $this->flattenRoutes($item))
            ->filter()
            ->values()
            ->all();

        $this->assertNotContains('modules.admission-inpatient.consent-templates.index', $routes);
    }

    public function test_home_url_includes_required_route_parameters(): void
    {
        $this->startSession();
        session()->flush();
        session([
            'authenticated' => true,
            'hr_user_id' => 10,
            'hr_user_level' => 2,
            'hr_branch_id' => 1,
            'companies_groups_id' => 1,
            'groupid' => 0,
        ]);

        $landing = app(NavigationService::class)->homeLanding();

        if ($landing['route'] === 'modules.admission-inpatient.calculator.index') {
            $this->assertArrayHasKey('type', $landing['params']);
        }

        $this->assertStringNotContainsString('Missing required parameter', app(NavigationService::class)->homeUrl());
    }

    /** @return array<string, int|bool> */
    private function branchSession(): array
    {
        return [
            'authenticated' => true,
            'hr_user_id' => 10,
            'hr_user_level' => 2,
            'hr_branch_id' => 1,
            'companies_groups_id' => 1,
            'groupid' => 0,
        ];
    }

    /** @return list<string|null> */
    private function flattenRoutes(object $item): array
    {
        $routes = [$item->route ?? null];
        foreach ($item->children ?? [] as $child) {
            $routes = [...$routes, ...$this->flattenRoutes($child)];
        }

        return $routes;
    }
}
