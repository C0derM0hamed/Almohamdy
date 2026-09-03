<?php

namespace Database\Seeders;

use App\Support\DemoAccounts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * License Management client-review demo accounts — two independent sets:
 *
 * 1. Client accounts (match LICENSE_MANAGEMENT_CLIENT_REVIEW_AR.pdf) → bader OTP inbox
 * 2. Internal Mohamed test accounts (separate usernames) → mohamed OTP inbox
 *
 * Same password (CLIENT_TEST_PASSWORD). No email overlap conflict between sets.
 *
 * Run with:
 * php artisan db:seed --class='Database\\Seeders\\LicenseClientReviewSeeder' --force
 */
class LicenseClientReviewSeeder extends Seeder
{
    private const CLIENT_OTP_EMAIL = 'bader_alhajji@hotmail.com';

    private const MOHAMED_OTP_EMAIL = 'mohamedmostafa2011360@gmail.com';

    /**
     * @var array<string, array{first:string,last:string,email:string,level:string,branch_id:int,groupid:int,permissions:list<string>}>
     */
    private const ACCOUNTS = [
        // ── Client (PDF) ──
        'lic_cc_super_mohamed' => [
            'first' => 'Mohamed', 'last' => 'CC Supervisor', 'email' => self::CLIENT_OTP_EMAIL,
            'level' => '1', 'branch_id' => 1, 'groupid' => 0,
            'permissions' => ['licenses.view', 'licenses.process', 'licenses.export', 'licenses_admin'],
        ],
        'lic_responsible_mohamed' => [
            'first' => 'Mohamed', 'last' => 'License Responsible', 'email' => self::CLIENT_OTP_EMAIL,
            'level' => '2', 'branch_id' => 1, 'groupid' => 0,
            'permissions' => ['licenses.view', 'licenses.process'],
        ],
        'lic_finance_bader' => [
            'first' => 'Bader', 'last' => 'Finance Manager', 'email' => self::CLIENT_OTP_EMAIL,
            'level' => '1', 'branch_id' => 1, 'groupid' => 1,
            'permissions' => ['licenses.view', 'licenses_finance'],
        ],
        'lic_super_bader' => [
            'first' => 'Bader', 'last' => 'Super Admin', 'email' => self::CLIENT_OTP_EMAIL,
            'level' => '3', 'branch_id' => 1, 'groupid' => 0,
            'permissions' => [],
        ],
        'lic_cc_super_bader' => [
            'first' => 'Bader', 'last' => 'CC Supervisor', 'email' => self::CLIENT_OTP_EMAIL,
            'level' => '1', 'branch_id' => 1, 'groupid' => 0,
            'permissions' => ['licenses.view', 'licenses.process', 'licenses.export', 'licenses_admin'],
        ],
        'lic_responsible_bader' => [
            'first' => 'Bader', 'last' => 'License Responsible', 'email' => self::CLIENT_OTP_EMAIL,
            'level' => '2', 'branch_id' => 1, 'groupid' => 0,
            'permissions' => ['licenses.view', 'licenses.process'],
        ],
        'lic_finance_mohamed' => [
            'first' => 'Mohamed', 'last' => 'Finance Manager', 'email' => self::CLIENT_OTP_EMAIL,
            'level' => '1', 'branch_id' => 1, 'groupid' => 1,
            'permissions' => ['licenses.view', 'licenses_finance'],
        ],
        'lic_super_mohamed' => [
            'first' => 'Mohamed', 'last' => 'Super Admin', 'email' => self::CLIENT_OTP_EMAIL,
            'level' => '3', 'branch_id' => 1, 'groupid' => 0,
            'permissions' => [],
        ],
        // ── Internal Mohamed test (separate usernames) ──
        'lic_mo_cc_super' => [
            'first' => 'Mohamed', 'last' => 'Test CC Supervisor', 'email' => self::MOHAMED_OTP_EMAIL,
            'level' => '1', 'branch_id' => 1, 'groupid' => 0,
            'permissions' => ['licenses.view', 'licenses.process', 'licenses.export', 'licenses_admin'],
        ],
        'lic_mo_responsible' => [
            'first' => 'Mohamed', 'last' => 'Test License Responsible', 'email' => self::MOHAMED_OTP_EMAIL,
            'level' => '2', 'branch_id' => 1, 'groupid' => 0,
            'permissions' => ['licenses.view', 'licenses.process'],
        ],
        'lic_mo_finance' => [
            'first' => 'Mohamed', 'last' => 'Test Finance Manager', 'email' => self::MOHAMED_OTP_EMAIL,
            'level' => '1', 'branch_id' => 1, 'groupid' => 1,
            'permissions' => ['licenses.view', 'licenses_finance'],
        ],
        'lic_mo_super' => [
            'first' => 'Mohamed', 'last' => 'Test Super Admin', 'email' => self::MOHAMED_OTP_EMAIL,
            'level' => '3', 'branch_id' => 1, 'groupid' => 0,
            'permissions' => [],
        ],
    ];

    public function run(): void
    {
        if (! Schema::hasTable('ra_users')) {
            $this->command?->warn('Skipped LicenseClientReviewSeeder: ra_users does not exist.');

            return;
        }

        $password = (string) env('CLIENT_TEST_PASSWORD', '');

        if ($password === '') {
            $this->command?->warn('Skipped LicenseClientReviewSeeder: set CLIENT_TEST_PASSWORD in .env before seeding.');

            return;
        }

        $hashedPassword = hash('sha256', $password);
        $now = now();
        $companyGroupId = (int) (DB::table('companies_groups')->orderBy('id')->value('id') ?: 1);

        DB::transaction(function () use ($hashedPassword, $now, $companyGroupId): void {
            foreach (self::ACCOUNTS as $username => $account) {
                if (! DemoAccounts::isDemoUsername($username)) {
                    throw new \LogicException("Username [{$username}] is not registered in ".DemoAccounts::class);
                }

                DB::table('ra_users')->updateOrInsert(
                    ['hr_username' => $username],
                    array_filter([
                        'hr_first_name' => $account['first'],
                        'hr_last_name' => $account['last'],
                        'hr_email_address' => $account['email'],
                        'hr_password' => $hashedPassword,
                        'hr_info' => Schema::hasColumn('ra_users', 'hr_info')
                            ? (str_starts_with($username, 'lic_mo_')
                                ? 'Internal license review test account (Mohamed inbox).'
                                : 'License client review demo account (client inbox).')
                            : null,
                        'hr_user_level' => $account['level'],
                        'hr_signup_date' => $now->format('Y-m-d H:i:s'),
                        'activated' => '1',
                        'upload_no' => 1000,
                        'totalpost' => 0,
                        'groupid' => $account['groupid'],
                        'm_group' => 1,
                        'status' => 3,
                        'companies_groups_id' => $companyGroupId,
                        'branch_id' => $account['branch_id'],
                        'failed_login_attempts' => 0,
                        'last_failed_login' => null,
                        'isSearchedField' => Schema::hasColumn('ra_users', 'isSearchedField') ? '1' : null,
                        'lastPassChange' => Schema::hasColumn('ra_users', 'lastPassChange') ? (string) time() : null,
                        'department_supervisor' => Schema::hasColumn('ra_users', 'department_supervisor') ? '0' : null,
                        'mobile' => null,
                        'idno' => null,
                        'updated_at' => $now,
                    ], fn ($value) => $value !== null),
                );

                $userId = (int) DB::table('ra_users')->where('hr_username', $username)->value('hr_id');

                if ($userId > 0 && Schema::hasTable('user_permission') && $account['permissions'] !== []) {
                    $this->grantPermissions($userId, $account['permissions']);
                }
            }
        });

        $this->command?->info('License review accounts ready: 8 client + 4 internal Mohamed test.');
    }

    /** @param list<string> $permissions */
    private function grantPermissions(int $userId, array $permissions): void
    {
        foreach ($permissions as $permission) {
            DB::table('user_permission')->updateOrInsert(
                ['userid' => $userId, 'page' => $permission],
                ['pageid' => 0, 'permit' => '2'],
            );
        }
    }
}
