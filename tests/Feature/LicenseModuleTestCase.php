<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\LicenseAuthority;
use App\Models\LicenseStatus;
use App\Models\LicenseType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class LicenseModuleTestCase extends TestCase
{
    /** @var list<Migration> */
    protected array $licenseMigrations = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'hm.permissions.bypass' => false,
            'hm.permissions.admin_levels' => [3],
            'hm.permissions.enforcement_mode' => 'strict',
            'hm.licenses.notifications.enabled' => true,
            'hm.licenses.notifications.mail' => false,
            'hm.licenses.notifications.sms' => false,
            'hm.licenses.alert_days' => [90, 60, 30],
            'hm.licenses.undertaking_escalation_days' => 3,
            'hm.licenses.expired_reescalate_days' => 7,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::dropAllTables();

        $this->createLegacySchema();
        $this->runLicenseMigrations();
        $this->seedBranchesAndUsers();

        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();
        $this->app['url']->forceRootUrl('http://localhost');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    protected function createLegacySchema(): void
    {
        Schema::create('companies_groups', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name_ar');
            $table->string('name_en');
        });

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
            $table->unsignedInteger('companies_groups_id')->index();
            $table->boolean('publish')->default(true);
            $table->unsignedInteger('ranking')->default(0);
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

    protected function runLicenseMigrations(): void
    {
        $paths = [
            'database/migrations/2026_08_30_000009_create_license_reference_tables.php',
            'database/migrations/2026_08_30_000010_create_licenses_and_renewal_tables.php',
            'database/migrations/2026_08_30_000011_create_license_payment_tables.php',
            'database/migrations/2026_08_30_000012_create_license_activity_tables.php',
            'database/migrations/2026_08_30_000013_create_license_escalation_and_notification_tables.php',
        ];

        foreach ($paths as $path) {
            /** @var Migration $migration */
            $migration = require base_path($path);
            $migration->up();
            $this->licenseMigrations[] = $migration;
        }
    }

    protected function seedBranchesAndUsers(): void
    {
        DB::table('companies_groups')->insert([
            ['id' => 1, 'name_ar' => 'مستشفى النزهة', 'name_en' => 'Al-Nuzha Hospital'],
            ['id' => 2, 'name_ar' => 'مستشفى السويدي', 'name_en' => 'Al-Suwaidi Hospital'],
        ]);
        DB::table('branches')->insert([
            ['id' => 1, 'name_ar' => 'الطوارئ', 'name_en' => 'Emergency', 'companies_groups_id' => 1, 'publish' => true, 'ranking' => 1],
            ['id' => 2, 'name_ar' => 'المختبر', 'name_en' => 'Laboratory', 'companies_groups_id' => 1, 'publish' => true, 'ranking' => 2],
            ['id' => 3, 'name_ar' => 'الشؤون القانونية', 'name_en' => 'Legal Affairs', 'companies_groups_id' => 2, 'publish' => true, 'ranking' => 1],
        ]);

        foreach ([
            [1, 'Admin', 3, 1, 1],
            [10, 'Owner One', 1, 1, 1],
            [11, 'Owner Two', 1, 1, 1],
            [12, 'Branch Two Owner', 1, 2, 1],
            [20, 'Finance', 1, 1, 1],
            [30, 'Super Admin', 3, 1, 1],
            [40, 'Other Company', 1, 3, 2],
        ] as [$id, $name, $level, $branchId, $companyId]) {
            [$first, $last] = array_pad(explode(' ', $name, 2), 2, 'User');
            DB::table('ra_users')->insert([
                'hr_id' => $id,
                'hr_first_name' => $first,
                'hr_last_name' => $last,
                'hr_email_address' => strtolower(str_replace(' ', '.', $name)).'@example.test',
                'hr_username' => 'user'.$id,
                'hr_password' => 'unused',
                'hr_user_level' => $level,
                'branch_id' => $branchId,
                'companies_groups_id' => $companyId,
                'groupid' => $id,
                'mobile' => '050000'.str_pad((string) $id, 4, '0', STR_PAD_LEFT),
                'activated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function grant(int $userId, string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            DB::table('user_permission')->insert([
                'userid' => $userId,
                'pageid' => 0,
                'page' => $permission,
                'permit' => '2',
            ]);
        }
    }

    protected function actAsLicenseUser(int $userId): User
    {
        $user = User::query()->findOrFail($userId);
        session()->flush();
        session([
            'hr_user_id' => (int) $user->getKey(),
            'hr_user_level' => (int) $user->hr_user_level,
            'hr_branch_id' => (int) $user->branch_id,
            'companies_groups_id' => (int) $user->companies_groups_id,
            'groupid' => (int) $user->groupid,
        ]);

        return $user;
    }

    /** @param list<int>|null $branchIds */
    protected function makeLicense(
        int $ownerId = 10,
        int $companyId = 1,
        ?array $branchIds = null,
        string $statusCode = 'active',
        string $expiryDate = '2027-01-01',
        ?string $title = null,
    ): License {
        $branchIds ??= [$companyId === 1 ? 1 : 3];
        $authority = LicenseAuthority::query()->firstOrCreate(
            ['companies_groups_id' => $companyId, 'name_en' => 'Authority '.$companyId],
            ['name_ar' => 'جهة '.$companyId, 'publish' => true, 'ranking' => 1],
        );
        $type = LicenseType::query()->firstOrCreate(
            ['companies_groups_id' => $companyId, 'name_en' => 'Type '.$companyId],
            ['name_ar' => 'نوع '.$companyId, 'publish' => true, 'ranking' => 1],
        );

        $license = License::query()->create([
            'companies_groups_id' => $companyId,
            'license_authority_id' => $authority->getKey(),
            'license_type_id' => $type->getKey(),
            'license_number' => (string) random_int(100000, 999999),
            'title' => $title ?? 'License '.uniqid(),
            'responsible_user_id' => $ownerId,
            'issue_date' => '2026-01-01',
            'expiry_date' => $expiryDate,
            'status_id' => LicenseStatus::query()->where('code', $statusCode)->value('id'),
            'renewal_stage_id' => DB::table('license_renewal_stages')->where('code', 'not_started')->value('id'),
            'publish' => true,
            'created_by' => 1,
        ]);
        $license->branches()->sync($branchIds);

        return $license->fresh();
    }
}
