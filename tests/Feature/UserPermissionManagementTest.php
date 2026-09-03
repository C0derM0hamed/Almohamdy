<?php

namespace Tests\Feature;

use App\Services\Auth\PermissionRegistry;
use App\Services\Dashboard\NavigationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserPermissionManagementTest extends TestCase
{
    protected $baseUrl = 'http://localhost';

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['url']->forceRootUrl('http://localhost');
        config(['hm.permissions.bypass' => false, 'hm.permissions.admin_levels' => [3]]);

        foreach (['ra_users', 'user_permission', 'user_groups_permission', 'user_groups', 'branches', 'companies_groups'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::dropIfExists('users');
        Schema::create('ra_users', function (Blueprint $table) {
            $table->increments('hr_id'); $table->string('hr_first_name'); $table->string('hr_last_name')->nullable();
            $table->string('hr_email_address')->unique(); $table->string('hr_username')->unique(); $table->string('hr_password')->default('');
            $table->string('hr_user_level')->default('0'); $table->integer('branch_id'); $table->integer('companies_groups_id');
            $table->integer('groupid')->default(0); $table->string('mobile')->nullable(); $table->string('activated')->default('1');
            $table->string('hr_last_login')->default('0000-00-00 00:00:00'); $table->string('lastPassChange')->default('0');
            $table->string('department_supervisor')->default('0'); $table->integer('job_title')->nullable(); $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('last_failed_login')->nullable(); $table->integer('created_by')->nullable(); $table->integer('updated_by')->nullable(); $table->string('hr_signup_date')->default(''); $table->timestamps();
            $table->string('isSearchedField')->default('1');
        });
        Schema::create('user_permission', function (Blueprint $table) { $table->increments('id'); $table->integer('userid'); $table->integer('pageid')->default(0); $table->string('page'); $table->string('permit'); });
        Schema::create('user_groups_permission', function (Blueprint $table) { $table->increments('id'); $table->integer('groupid'); $table->string('page'); $table->string('permit'); });
        Schema::create('user_groups', function (Blueprint $table) { $table->increments('id'); $table->string('name_ar'); $table->string('name_en')->nullable(); $table->integer('publish')->default(1); });
        Schema::create('branches', function (Blueprint $table) { $table->increments('id'); $table->string('name_ar'); $table->string('name_en')->nullable(); $table->integer('companies_groups_id'); });
        Schema::create('companies_groups', function (Blueprint $table) { $table->increments('id'); $table->string('name_ar'); $table->string('name_en')->nullable(); });

        DB::table('companies_groups')->insert([['id' => 1, 'name_ar' => 'أ'], ['id' => 2, 'name_ar' => 'ب']]);
        DB::table('branches')->insert([['id' => 10, 'name_ar' => 'أ1', 'companies_groups_id' => 1], ['id' => 20, 'name_ar' => 'ب1', 'companies_groups_id' => 2]]);
        DB::table('user_groups')->insert(['id' => 5, 'name_ar' => 'مجموعة', 'publish' => 1]);
        DB::table('user_groups_permission')->insert([['groupid' => 5, 'page' => 'users', 'permit' => '2'], ['groupid' => 5, 'page' => 'user_groups_permissins', 'permit' => '2'], ['groupid' => 5, 'page' => 'reports', 'permit' => '2']]);
        $this->user(1, 3, 1, 10); $this->user(2, 1, 1, 10, 5); $this->user(3, 1, 2, 20); $this->user(4, 3, 1, 10);
    }

    public function test_standard_user_cannot_open_permission_management_url(): void
    {
        $this->withSession($this->sessionFor(3))->get('/modules/system-administration/users')->assertForbidden();
    }

    public function test_permission_admin_can_open_the_create_user_form(): void
    {
        $this->withSession($this->sessionFor(2, 5))
            ->get('/modules/system-administration/users/create')
            ->assertOk()
            ->assertSee(__('system_administration.users.create'))
            ->assertSee('data-direct-permissions')
            ->assertSee('data-direct-category-toggle')
            ->assertSee('data-direct-category-body')
            ->assertSee('hidden', false);
    }

    public function test_permission_admin_is_limited_to_own_company_and_branch(): void
    {
        $this->withSession($this->sessionFor(2, 5))->get('/modules/system-administration/users/3')->assertForbidden();
        $this->withSession($this->sessionFor(2, 5))->get('/modules/system-administration/users/2')->assertOk();
    }

    public function test_super_administrator_can_manage_users_across_every_company_and_branch(): void
    {
        $this->withSession($this->sessionFor(1))
            ->get('/modules/system-administration/users')
            ->assertOk()
            ->assertSee('User 2')
            ->assertSee('User 3');

        $this->withSession($this->sessionFor(1))
            ->get('/modules/system-administration/users/3/edit')
            ->assertOk();

        $this->withSession($this->sessionFor(1))
            ->post('/modules/system-administration/users', [
                'hr_first_name' => 'Global',
                'hr_last_name' => 'Manager',
                'hr_email_address' => 'global-manager@test.local',
                'hr_username' => 'global_manager',
                'password' => 'SecurePass#2026',
                'password_confirmation' => 'SecurePass#2026',
                'mobile' => '',
                'hr_user_level' => '1',
                'companies_groups_id' => 2,
                'branch_id' => 20,
                'branch_ids' => [20],
                'groupid' => 0,
                'activated' => '1',
                'permissions' => ['users'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ra_users', [
            'hr_username' => 'global_manager',
            'companies_groups_id' => 2,
            'branch_id' => 20,
        ]);
    }

    public function test_permission_admin_cannot_grant_permission_they_do_not_hold(): void
    {
        DB::table('user_permission')->insert(['userid' => 1, 'pageid' => 0, 'page' => 'super_only', 'permit' => '2']);
        $payload = $this->payload(2, ['users', 'user_groups_permissins', 'super_only']);
        $this->withSession($this->sessionFor(2, 5))->put('/modules/system-administration/users/2', $payload)->assertForbidden();
    }

    public function test_permission_admin_cannot_assign_group_with_permissions_they_do_not_hold(): void
    {
        DB::table('user_groups')->insert(['id' => 6, 'name_ar' => 'Elevated', 'publish' => 1]);
        DB::table('user_groups_permission')->insert(['groupid' => 6, 'page' => 'super_only', 'permit' => '2']);
        DB::table('user_permission')->insert(['userid' => 1, 'pageid' => 0, 'page' => 'super_only', 'permit' => '2']);
        $payload = $this->payload(2, ['users', 'user_groups_permissins'], ['groupid' => 6]);
        $this->withSession($this->sessionFor(2, 5))->put('/modules/system-administration/users/2', $payload)->assertForbidden();
    }

    public function test_user_management_navigation_uses_the_same_permission_admin_gate(): void
    {
        $this->startSession();
        session($this->sessionFor(2, 5));
        $authorizedRoutes = $this->sidebarRoutes();
        $this->assertContains('modules.system-admin.users.index', $authorizedRoutes);

        session()->flush();
        session($this->sessionFor(3));
        $standardRoutes = $this->sidebarRoutes();
        $this->assertNotContains('modules.system-admin.users.index', $standardRoutes);
    }

    public function test_final_active_super_administrator_is_protected(): void
    {
        DB::table('ra_users')->where('hr_id', 4)->update(['activated' => '0']);
        $payload = $this->payload(1, [], ['hr_user_level' => '1']);
        $this->withSession($this->sessionFor(1))->put('/modules/system-administration/users/1', $payload)->assertSessionHasErrors('hr_user_level');
    }

    public function test_permission_admin_can_override_an_inherited_permission_with_a_direct_deny(): void
    {
        $this->user(5, 1, 1, 10, 5);
        $this->withSession($this->sessionFor(2, 5))
            ->get('/modules/system-administration/users/5/permissions')
            ->assertOk()
            ->assertSee('إدارة صلاحيات المستخدم')
            ->assertSee('من المجموعة');
        $version = app(\App\Repositories\SystemAdministration\UserPermissionAdminRepository::class)->permissionVersion(5, 5);

        $this->withSession($this->sessionFor(2, 5))
            ->put('/modules/system-administration/users/5/permissions', [
                'permissions_version' => $version,
                'decisions' => ['users' => 'deny', 'user_groups_permissins' => 'inherit', 'reports' => 'inherit'],
            ])->assertRedirect('/modules/system-administration/users/5/permissions');

        $this->assertDatabaseHas('user_permission', ['userid' => 5, 'page' => 'users', 'permit' => '1']);
        $states = app(\App\Repositories\Auth\PermissionRepository::class)->permissionStatesForUser(5, 5);
        $this->assertSame('deny', $states['users']['decision']);
        $this->assertSame('direct', $states['users']['source']);
    }

    public function test_permission_catalog_is_grouped_and_only_contains_supported_module_actions(): void
    {
        $permissions = app(PermissionRegistry::class)->permissions();

        $this->assertTrue($permissions->contains(fn (array $permission): bool => $permission['category'] === 'emergency'));
        $this->assertTrue($permissions->contains(fn (array $permission): bool => $permission['code'] === 'doctors.view'));
        $this->assertFalse($permissions->contains(fn (array $permission): bool => $permission['code'] === 'doctors.delete'));
        $this->assertSame(['emergency_follow_up.process'], app(PermissionRegistry::class)->codesForRoute('modules.emergency-follow-up.close'));
    }

    public function test_permission_update_rejects_a_stale_version(): void
    {
        $this->user(5, 1, 1, 10);
        $this->withSession($this->sessionFor(2, 5))
            ->put('/modules/system-administration/users/5/permissions', [
                'permissions_version' => str_repeat('a', 64),
                'decisions' => ['users' => 'allow'],
            ])->assertSessionHasErrors('permissions_version');
    }

    public function test_strict_mode_blocks_a_catalogued_route_without_its_permission(): void
    {
        config(['hm.permissions.enforcement_mode' => 'strict']);
        $this->withSession($this->sessionFor(3))
            ->get(route('modules.doctors'))
            ->assertForbidden();
    }

    private function user(int $id, int $level, int $company, int $branch, int $group = 0): void
    {
        DB::table('ra_users')->insert(['hr_id' => $id, 'hr_first_name' => 'User '.$id, 'hr_email_address' => "u{$id}@test.local", 'hr_username' => "u{$id}", 'hr_user_level' => (string) $level, 'branch_id' => $branch, 'companies_groups_id' => $company, 'groupid' => $group, 'activated' => '1']);
    }

    private function sessionFor(int $id, int $group = 0): array
    {
        $user = DB::table('ra_users')->where('hr_id', $id)->first();
        return ['hr_user_id' => $id, 'hr_user_level' => (int) $user->hr_user_level, 'companies_groups_id' => $user->companies_groups_id, 'hr_branch_id' => $user->branch_id, 'groupid' => $group];
    }

    private function payload(int $id, array $permissions = [], array $override = []): array
    {
        $user = DB::table('ra_users')->where('hr_id', $id)->first();
        return array_merge(['hr_first_name' => $user->hr_first_name, 'hr_last_name' => '', 'hr_email_address' => $user->hr_email_address, 'hr_username' => $user->hr_username, 'password' => '', 'password_confirmation' => '', 'mobile' => '', 'hr_user_level' => $user->hr_user_level, 'companies_groups_id' => $user->companies_groups_id, 'branch_id' => $user->branch_id, 'groupid' => $user->groupid, 'activated' => $user->activated, 'permissions' => $permissions], $override);
    }

    /** @return list<string|null> */
    private function sidebarRoutes(): array
    {
        return collect(app(NavigationService::class)->sidebar())
            ->flatMap(fn ($item) => $item->hasChildren() ? collect($item->children)->pluck('route') : [$item->route])
            ->filter()
            ->values()
            ->all();
    }
}
