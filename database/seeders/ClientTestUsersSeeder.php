<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the four client-facing test login accounts (one per main role),
 * using real inbox-delivering email aliases so OTP verification works
 * end to end. Branch/company/group scope is copied from the existing
 * PW_AUDIT_* accounts (see LocalDemoUsersSeeder). Runs in every
 * environment (local and the VPS) but only ever touches these four
 * hr_username rows, so it never affects other business users.
 */
class ClientTestUsersSeeder extends Seeder
{
    /** @var list<array{username:string,email:string,level:string,branch:int,group:int,companies_groups_id:int}> */
    private const USERS = [
        [
            'username' => 'CLIENT_TEST_SUPER_ADMIN',
            'email' => 'mohamedmostafa2011360+super@gmail.com',
            'level' => '3',
            'branch' => 1,
            'group' => 0,
            'companies_groups_id' => 1,
        ],
        [
            'username' => 'CLIENT_TEST_PERM_ADMIN',
            'email' => 'mohamedmostafa2011360+permission@gmail.com',
            'level' => '1',
            'branch' => 1,
            'group' => 1,
            'companies_groups_id' => 1,
        ],
        [
            'username' => 'CLIENT_TEST_BRANCH_A',
            'email' => 'mohamedmostafa2011360+brancha@gmail.com',
            'level' => '2',
            'branch' => 1,
            'group' => 0,
            'companies_groups_id' => 1,
        ],
        [
            'username' => 'CLIENT_TEST_BRANCH_B',
            'email' => 'mohamedmostafa2011360+branchb@gmail.com',
            'level' => '4',
            'branch' => 2,
            'group' => 0,
            'companies_groups_id' => 1,
        ],
    ];

    public function run(): void
    {
        if (! Schema::hasTable('ra_users')) {
            $this->command?->warn('Skipped ClientTestUsersSeeder: ra_users does not exist. Import the legacy schema first.');

            return;
        }

        $password = (string) env('CLIENT_TEST_PASSWORD', '');

        if ($password === '') {
            $this->command?->warn('Skipped ClientTestUsersSeeder: set CLIENT_TEST_PASSWORD before seeding.');

            return;
        }

        $hashedPassword = hash('sha256', $password);
        $now = now();

        DB::transaction(function () use ($hashedPassword, $now): void {
            foreach (self::USERS as $user) {
                DB::table('ra_users')->updateOrInsert(
                    ['hr_username' => $user['username']],
                    [
                        'hr_first_name' => $user['username'],
                        'hr_last_name' => 'Client Test',
                        'hr_email_address' => $user['email'],
                        'hr_password' => $hashedPassword,
                        'hr_info' => 'Client test account for role verification; rotate/remove after acceptance.',
                        'hr_user_level' => $user['level'],
                        'hr_signup_date' => $now->format('Y-m-d H:i:s'),
                        'activated' => '1',
                        'upload_no' => 1000,
                        'totalpost' => 0,
                        'groupid' => $user['group'],
                        'm_group' => 1,
                        'status' => 3,
                        'companies_groups_id' => $user['companies_groups_id'],
                        'branch_id' => $user['branch'],
                        'failed_login_attempts' => 0,
                        'last_failed_login' => null,
                        'isSearchedField' => '1',
                        'lastPassChange' => (string) time(),
                        'department_supervisor' => '0',
                        'mobile' => null,
                        'idno' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        });

        $this->command?->info('Client test login accounts are ready.');
    }
}
