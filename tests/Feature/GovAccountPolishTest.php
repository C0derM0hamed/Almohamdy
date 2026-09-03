<?php

namespace Tests\Feature;

use App\Services\Auth\PermissionRegistry;
use Database\Seeders\GovAccountReferenceSeeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class GovAccountPolishTest extends GovAccountModuleTestCase
{
    public function test_permission_grant_migration_is_idempotent_and_non_destructive_on_rollback(): void
    {
        DB::table('ra_users')->insert(['hr_id' => 50, 'hr_first_name' => 'Mohamed', 'hr_last_name' => 'Tester', 'hr_email_address' => 'mohamed@example.test', 'hr_username' => 'mohamed_permission', 'hr_password' => 'unused', 'hr_user_level' => 1, 'branch_id' => 1, 'companies_groups_id' => 1, 'groupid' => 50, 'activated' => true, 'created_at' => now(), 'updated_at' => now()]);
        $migration = require base_path('database/migrations/2026_09_01_000019_grant_gov_account_permissions_to_approved_test_users.php');
        $migration->up();
        $migration->up();
        $this->assertSame(6, DB::table('user_permission')->where('userid', 50)->where('permit', '2')->count());
        $migration->down();
        $this->assertSame(6, DB::table('user_permission')->where('userid', 50)->count());
    }

    public function test_operational_routes_have_admin_coverage_and_self_service_stays_auth_only(): void
    {
        $registry = app(PermissionRegistry::class);
        $selfService = [
            'modules.gov-accounts.undertakings.index', 'modules.gov-accounts.undertakings.show', 'modules.gov-accounts.undertakings.accept',
            'modules.gov-accounts.my-accounts.index', 'modules.gov-accounts.my-accounts.show',
            'modules.gov-accounts.notifications.index', 'modules.gov-accounts.notifications.read',
        ];
        $routes = collect(Route::getRoutes())->map(fn ($route) => $route->getName())->filter(fn ($name) => is_string($name) && str_starts_with($name, 'modules.gov-accounts.'));
        foreach ($routes as $routeName) {
            $codes = $registry->codesForRoute($routeName);
            if (in_array($routeName, $selfService, true)) {
                $this->assertSame([], $codes, $routeName.' must remain authenticated self-service.');
            } else {
                $this->assertContains('gov_accounts_admin', $codes, $routeName.' must allow the module administrator.');
            }
        }
    }

    public function test_arabic_and_english_translation_trees_match_and_pages_render_in_both_locales(): void
    {
        $english = require base_path('lang/en/gov_accounts.php');
        $arabic = require base_path('lang/ar/gov_accounts.php');
        $this->assertSame(array_keys(Arr::dot($english)), array_keys(Arr::dot($arabic)));
        foreach (['actions.search', 'timeline.lifecycle_requested', 'timeline.lifecycle_completed', 'timeline.account_status_reverted', 'notifications.lifecycle_subject', 'notifications.lifecycle_completed_subject', 'validation.account_request_in_progress'] as $key) {
            $this->assertNotSame('gov_accounts.'.$key, __('gov_accounts.'.$key));
        }

        $this->seed(GovAccountReferenceSeeder::class);
        $this->grant(10, 'gov_accounts_admin');
        $this->actAsGovAccountUser(10);
        session(['locale' => 'en']);
        $this->get(route('modules.gov-accounts.dashboard'))->assertOk()->assertSee('Official Accounts Dashboard');
        $this->get(route('modules.gov-accounts.notices.index'))->assertOk()->assertSee('Meetings and training notices');
        session(['locale' => 'ar']);
        $this->get(route('modules.gov-accounts.dashboard'))->assertOk()->assertSee('لوحة الحسابات الرسمية');
        $this->get(route('modules.gov-accounts.admin.index'))->assertOk()->assertSee('إعدادات الحسابات الرسمية');

        $this->actAsGovAccountUser(11);
        $this->get(route('modules.gov-accounts.undertakings.index'))->assertOk();
        $this->get(route('modules.gov-accounts.my-accounts.index'))->assertOk();
        $this->get(route('modules.gov-accounts.notifications.index'))->assertOk();
    }
}
