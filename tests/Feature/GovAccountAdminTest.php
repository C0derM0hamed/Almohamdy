<?php

namespace Tests\Feature;

use Database\Seeders\GovAccountReferenceSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GovAccountAdminTest extends GovAccountModuleTestCase
{
    public function test_migrations_are_idempotent_reversible_and_never_create_a_password_column(): void
    {
        foreach ($this->govAccountMigrations as $migration) {
            $migration->up();
        }
        $this->assertTrue(Schema::hasTable('gov_accounts'));
        $this->assertFalse(Schema::hasColumn('gov_accounts', 'password'));
        $this->assertFalse(Schema::hasColumn('gov_accounts', 'secret'));
        foreach (array_reverse($this->govAccountMigrations) as $migration) {
            $migration->down();
        }
        $this->assertFalse(Schema::hasTable('gov_account_authorities'));
    }

    public function test_reference_seeder_is_idempotent_and_preserves_custom_records(): void
    {
        DB::table('gov_account_roles')->insert(['companies_groups_id' => 1, 'name_ar' => 'مخصص', 'name_en' => 'Custom', 'publish' => true, 'ranking' => 99, 'created_at' => now(), 'updated_at' => now()]);
        $this->seed(GovAccountReferenceSeeder::class);
        $firstCount = DB::table('gov_account_roles')->count();
        $this->seed(GovAccountReferenceSeeder::class);
        $this->assertSame($firstCount, DB::table('gov_account_roles')->count());
        $this->assertDatabaseHas('gov_account_roles', ['name_en' => 'Custom']);
        $this->assertDatabaseHas('gov_account_request_statuses', ['code' => 'awaiting_employee']);
        $this->assertDatabaseHas('gov_account_statuses', ['code' => 'closure_requested']);
    }

    public function test_module_admin_can_manage_references_and_department_heads(): void
    {
        $this->grant(10, 'gov_accounts_admin');
        $this->actAsGovAccountUser(10);
        $this->get(route('modules.gov-accounts.admin.index'))->assertOk()->assertSee(__('gov_accounts.admin.title'));
        $this->post(route('modules.gov-accounts.admin.authorities.store'), ['name_ar' => 'جهة اختبار', 'name_en' => 'Test Authority', 'ranking' => 5, 'publish' => 1])->assertRedirect();
        $authorityId = (int) DB::table('gov_account_authorities')->where('name_en', 'Test Authority')->value('id');
        $this->post(route('modules.gov-accounts.admin.services.store'), ['authority_id' => $authorityId, 'name_ar' => 'خدمة اختبار', 'name_en' => 'Test Service', 'ranking' => 3, 'publish' => 1])->assertRedirect();
        $this->post(route('modules.gov-accounts.admin.roles.store'), ['name_ar' => 'مدقق', 'name_en' => 'Reviewer', 'publish' => 1])->assertRedirect();
        $this->post(route('modules.gov-accounts.admin.department-heads.store'), ['department_id' => 1, 'user_id' => 11, 'publish' => 1])->assertRedirect();
        $this->get(route('modules.gov-accounts.admin.services.index'))->assertOk()->assertSee('Test Service');
        $this->get(route('modules.gov-accounts.admin.department-heads.index'))->assertOk()->assertSee('Employee');
        $this->assertDatabaseHas('gov_account_services', ['companies_groups_id' => 1, 'authority_id' => $authorityId, 'name_en' => 'Test Service']);
        $this->assertDatabaseHas('gov_account_department_heads', ['companies_groups_id' => 1, 'department_id' => 1, 'user_id' => 11]);
    }

    public function test_admin_routes_require_permission_and_reject_cross_scope_payloads(): void
    {
        $this->actAsGovAccountUser(11);
        $this->get(route('modules.gov-accounts.admin.index'))->assertForbidden();
        $this->grant(10, 'gov_accounts_admin');
        $this->actAsGovAccountUser(10);
        $otherAuthority = DB::table('gov_account_authorities')->insertGetId(['companies_groups_id' => 2, 'name_ar' => 'خارجية', 'name_en' => 'Other', 'publish' => true, 'ranking' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->post(route('modules.gov-accounts.admin.services.store'), ['authority_id' => $otherAuthority, 'name_ar' => 'خدمة', 'name_en' => 'Service', 'publish' => 1])->assertSessionHasErrors('authority_id');
        $this->post(route('modules.gov-accounts.admin.department-heads.store'), ['department_id' => 3, 'user_id' => 40, 'publish' => 1])->assertSessionHasErrors();
        $this->assertDatabaseMissing('gov_account_department_heads', ['department_id' => 3, 'user_id' => 40]);
    }
}
