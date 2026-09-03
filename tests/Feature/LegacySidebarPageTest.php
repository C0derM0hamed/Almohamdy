<?php

namespace Tests\Feature;

use App\Services\LegacySidebarPageService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LegacySidebarPageTest extends TestCase
{
    public function test_all_old_sidebar_targets_have_a_new_design_route_and_legacy_alias(): void
    {
        $pages = app(LegacySidebarPageService::class)->all();

        $this->assertCount(30, $pages);

        $this->assertTrue(Route::has('legacy.sidebar.add_user_groups'));
        foreach (['sms_2', 'sms_5', 'sms_8', 'sms_12'] as $legacySmsPage) {
            $this->assertTrue(Route::has('legacy.sidebar.'.$legacySmsPage));
        }
        $this->assertTrue(Route::has('legacy.sidebar.rep_ss'));
        $this->assertTrue(Route::has('legacy.sidebar.sit_rep2'));
        $this->assertTrue(Route::has('legacy.case-compat'));
        $this->assertTrue(Route::has('legacy.branch-case-compat'));
        $this->assertTrue(Route::has('legacy.admin-case-compat'));

        foreach (array_keys($pages) as $page) {
            $this->assertTrue(Route::has('modules.legacy-sidebar.index'), $page);
            $this->assertTrue(Route::has('legacy.sidebar.'.$page), $page);
        }
    }

    public function test_new_design_legacy_module_requires_session_authentication(): void
    {
        $route = Route::getRoutes()->getByName('modules.legacy-sidebar.index');

        $this->assertNotNull($route);
        $this->assertContains('auth.session', $route->middleware());
    }

    public function test_old_case_subpage_urls_redirect_to_the_unified_case_workflow(): void
    {
        $this->withoutMiddleware()
            ->get('/commercial_cases_pdf.php?id=42')
            ->assertRedirect(route('modules.legacy-sidebar.pdf', ['commercial_cases', 42]));
        $this->withoutMiddleware()
            ->get('/commercial_cases_timeline.php?id=42')
            ->assertRedirect(route('modules.legacy-sidebar.show', ['commercial_cases', 42]));
        $this->withoutMiddleware()
            ->get('/branch/executive_title.php?id=9')
            ->assertRedirect(route('modules.legacy-sidebar.show', ['executive_title', 9]));
    }

    public function test_every_registered_page_declares_a_table_and_online_users_use_safe_columns(): void
    {
        $service = app(LegacySidebarPageService::class);

        foreach ($service->all() as $page => $spec) {
            $this->assertNotEmpty($spec['table'] ?? null, $page);
        }

        $onlineColumns = $service->spec('onlinetody')['display'];
        $this->assertNotContains('hr_password', $onlineColumns);
        $this->assertNotContains('token', $onlineColumns);
        $this->assertSame(
            ['hr_first_name', 'hr_last_name', 'hr_username', 'hr_email_address', 'mobile', 'hr_last_login', 'status'],
            $onlineColumns,
        );
    }
}
