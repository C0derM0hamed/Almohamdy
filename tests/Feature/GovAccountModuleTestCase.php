<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class GovAccountModuleTestCase extends TestCase
{
    /** @var list<Migration> */
    protected array $govAccountMigrations = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'hm.permissions.bypass' => false, 'hm.permissions.admin_levels' => [3], 'hm.permissions.enforcement_mode' => 'strict']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::dropAllTables();
        $this->createLegacySchema();
        $this->runGovAccountMigrations();
        $this->seedLegacyData();
        Storage::fake('local');
        Mail::fake();
        $this->app['url']->forceRootUrl('http://localhost');
    }

    protected function createLegacySchema(): void
    {
        Schema::create('ra_users', function (Blueprint $table): void {
            $table->increments('hr_id');
            $table->string('hr_first_name')->nullable();
            $table->string('hr_last_name')->nullable();
            $table->string('hr_email_address')->nullable();
            $table->string('hr_username')->nullable();
            $table->string('hr_password')->nullable();
            $table->unsignedInteger('hr_user_level')->default(1);
            $table->unsignedInteger('branch_id')->default(1);
            $table->unsignedInteger('companies_groups_id')->default(1);
            $table->unsignedInteger('groupid')->default(1);
            $table->string('mobile')->nullable();
            $table->boolean('activated')->default(true);
            $table->timestamps();
        });
        Schema::create('branches', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedInteger('companies_groups_id');
            $table->boolean('publish')->default(true);
            $table->unsignedInteger('ranking')->default(0);
        });
        Schema::create('branches_departments', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('branch_id')->index();
            $table->string('name_ar');
            $table->string('name_en');
            $table->boolean('publish')->default(true);
        });
        Schema::create('user_permission', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('userid')->index();
            $table->unsignedInteger('pageid')->default(0);
            $table->string('page')->index();
            $table->string('permit', 1);
        });
        Schema::create('user_groups_permission', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('groupid')->index();
            $table->string('page')->index();
            $table->string('permit', 1);
        });
    }

    protected function runGovAccountMigrations(): void
    {
        foreach (['database/migrations/2026_09_01_000015_create_gov_account_reference_tables.php', 'database/migrations/2026_09_01_000016_create_gov_account_core_tables.php', 'database/migrations/2026_09_01_000017_create_gov_account_activity_tables.php', 'database/migrations/2026_09_01_000018_create_gov_account_notice_tables.php'] as $path) {
            $migration = require base_path($path);
            $migration->up();
            $this->govAccountMigrations[] = $migration;
        }
    }

    protected function seedLegacyData(): void
    {
        DB::table('branches')->insert([
            ['id' => 1, 'name_ar' => 'فرع 1', 'name_en' => 'Branch 1', 'companies_groups_id' => 1, 'publish' => true, 'ranking' => 1],
            ['id' => 2, 'name_ar' => 'فرع 2', 'name_en' => 'Branch 2', 'companies_groups_id' => 1, 'publish' => true, 'ranking' => 2],
            ['id' => 3, 'name_ar' => 'شركة أخرى', 'name_en' => 'Other Company', 'companies_groups_id' => 2, 'publish' => true, 'ranking' => 1],
        ]);
        DB::table('branches_departments')->insert([
            ['id' => 1, 'branch_id' => 1, 'name_ar' => 'الطبية', 'name_en' => 'Medical', 'publish' => true],
            ['id' => 2, 'branch_id' => 2, 'name_ar' => 'المالية', 'name_en' => 'Finance', 'publish' => true],
            ['id' => 3, 'branch_id' => 3, 'name_ar' => 'الخارجية', 'name_en' => 'External', 'publish' => true],
        ]);
        foreach ([[1, 'Admin', 3, 1, 1], [10, 'Module Admin', 1, 1, 1], [11, 'Employee', 1, 1, 1], [40, 'Other Company', 1, 3, 2]] as [$id, $name, $level, $branch, $company]) {
            DB::table('ra_users')->insert(['hr_id' => $id, 'hr_first_name' => $name, 'hr_email_address' => 'user'.$id.'@example.test', 'hr_username' => 'user'.$id, 'hr_password' => 'unused', 'hr_user_level' => $level, 'branch_id' => $branch, 'companies_groups_id' => $company, 'groupid' => $id, 'activated' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    protected function grant(int $userId, string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            DB::table('user_permission')->insert(['userid' => $userId, 'pageid' => 0, 'page' => $permission, 'permit' => '2']);
        }
    }

    protected function actAsGovAccountUser(int $id): User
    {
        $user = User::query()->findOrFail($id);
        session()->flush();
        session(['hr_user_id' => $id, 'hr_user_level' => (int) $user->hr_user_level, 'hr_branch_id' => (int) $user->branch_id, 'companies_groups_id' => (int) $user->companies_groups_id, 'groupid' => (int) $user->groupid]);

        return $user;
    }
}
