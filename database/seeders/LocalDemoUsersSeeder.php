<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the four documented local login accounts.
 *
 * These accounts are intentionally limited to local/demo use. The legacy
 * application stores passwords as SHA-256 hex strings, so the plaintext
 * values below must be hashed with SHA-256 rather than Laravel's bcrypt.
 */
class LocalDemoUsersSeeder extends Seeder
{
    /** @var list<array{username:string,password:string,level:string,branch:int,group:int,mobile:string}> */
    private const USERS = [
        [
            'username' => 'PW_AUDIT_SUPER_ADMIN',
            'password' => 'f713583e9a1e8f04d5f4b3cd46c01f77',
            'level' => '3',
            'branch' => 1,
            'group' => 0,
            'mobile' => '9665000001001',
        ],
        [
            'username' => 'PW_AUDIT_PERMISSION_ADMIN',
            'password' => '6ebe4de6c0a90868285a7a22c4587331',
            'level' => '1',
            'branch' => 1,
            'group' => 1,
            'mobile' => '9665000001002',
        ],
        [
            'username' => 'PW_AUDIT_BRANCH_A',
            'password' => '7c314b2b3c690869c996c3f91e5b7b0e',
            'level' => '2',
            'branch' => 1,
            'group' => 0,
            'mobile' => '9665000001003',
        ],
        [
            'username' => 'PW_AUDIT_BRANCH_B',
            'password' => '2bdbb6fd3ed7954b41cdbc45be6981ec',
            'level' => '4',
            'branch' => 2,
            'group' => 0,
            'mobile' => '9665000001004',
        ],
    ];

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('Skipped LocalDemoUsersSeeder outside the local environment.');

            return;
        }

        if (! Schema::hasTable('ra_users')) {
            $this->command?->warn('Skipped LocalDemoUsersSeeder: ra_users does not exist. Import the legacy schema first.');

            return;
        }

        $now = now();

        DB::transaction(function () use ($now): void {
            foreach (self::USERS as $user) {
                DB::table('ra_users')->updateOrInsert(
                    ['hr_username' => $user['username']],
                    [
                        'hr_first_name' => $user['username'],
                        'hr_last_name' => 'Local Demo',
                        'hr_email_address' => strtolower($user['username']).'@audit.invalid',
                        'hr_password' => hash('sha256', $user['password']),
                        'hr_info' => 'Local demo account; do not use in production.',
                        'hr_user_level' => $user['level'],
                        'hr_signup_date' => $now->format('Y-m-d H:i:s'),
                        'activated' => '1',
                        'upload_no' => 1000,
                        'totalpost' => 0,
                        'groupid' => $user['group'],
                        'm_group' => 1,
                        'status' => 3,
                        'companies_groups_id' => 1,
                        'branch_id' => $user['branch'],
                        'failed_login_attempts' => 0,
                        'last_failed_login' => null,
                        'isSearchedField' => '1',
                        'lastPassChange' => (string) time(),
                        'department_supervisor' => '0',
                        'mobile' => $user['mobile'],
                        'idno' => substr($user['mobile'], -4),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        });

        $this->command?->info('Local demo login accounts are ready.');
    }
}
