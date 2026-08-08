<?php

namespace Database\Seeders;

use App\Support\DemoAccounts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the eight dedicated client demo login accounts (four roles for each
 * of the two real OTP inboxes).
 *
 * Login for these accounts is username + password -> OTP, because the two
 * inboxes are deliberately shared across four accounts each. The OTP is still
 * a real code sent through the configured SMTP mailer to the email stored on
 * the selected account.
 *
 * Scope (user level / branch / company group / permission group) is not
 * hardcoded from local IDs: it is copied at run time from the existing
 * PW_AUDIT_* reference accounts when they are present, so the same code
 * produces the right scope on the VPS. The seeder is idempotent and only ever
 * touches the eight `hr_username` rows listed in {@see DemoAccounts}.
 */
class ClientDemoUsersSeeder extends Seeder
{
    private const MOHAMED_EMAIL = 'mohamedmostafa@gmail.com';

    private const BADER_EMAIL = 'bader_alhajji@hotmail.com';

    /**
     * Reference account each role's scope is copied from, plus the fallback
     * scope used when that reference row does not exist in the database.
     *
     * @var array<string, array{reference:string,fallback:array{hr_user_level:string,branch_id:int,groupid:int,companies_groups_id:int}}>
     */
    private const ROLES = [
        'super' => [
            'reference' => 'PW_AUDIT_SUPER_ADMIN',
            'fallback' => ['hr_user_level' => '3', 'branch_id' => 1, 'groupid' => 0, 'companies_groups_id' => 1],
        ],
        'permission' => [
            'reference' => 'PW_AUDIT_PERMISSION_ADMIN',
            'fallback' => ['hr_user_level' => '1', 'branch_id' => 1, 'groupid' => 1, 'companies_groups_id' => 1],
        ],
        'brancha' => [
            'reference' => 'PW_AUDIT_BRANCH_A',
            'fallback' => ['hr_user_level' => '2', 'branch_id' => 1, 'groupid' => 0, 'companies_groups_id' => 1],
        ],
        'branchb' => [
            'reference' => 'PW_AUDIT_BRANCH_B',
            'fallback' => ['hr_user_level' => '4', 'branch_id' => 2, 'groupid' => 0, 'companies_groups_id' => 1],
        ],
    ];

    /** @var array<string, array{first:string,last:string,email:string}> */
    private const OWNERS = [
        'mohamed' => ['first' => 'Mohamed', 'last' => 'Demo', 'email' => self::MOHAMED_EMAIL],
        'bader' => ['first' => 'Bader', 'last' => 'Demo', 'email' => self::BADER_EMAIL],
    ];

    public function run(): void
    {
        if (! Schema::hasTable('ra_users')) {
            $this->command?->warn('Skipped ClientDemoUsersSeeder: ra_users does not exist. Import the legacy schema first.');

            return;
        }

        $password = (string) env('CLIENT_TEST_PASSWORD', '');

        if ($password === '') {
            $this->command?->warn('Skipped ClientDemoUsersSeeder: set CLIENT_TEST_PASSWORD in .env before seeding.');

            return;
        }

        // The legacy application stores passwords as SHA-256 hex strings.
        $hashedPassword = hash('sha256', $password);
        $now = now();

        DB::transaction(function () use ($hashedPassword, $now): void {
            foreach (self::OWNERS as $ownerKey => $owner) {
                foreach (self::ROLES as $roleKey => $role) {
                    $username = $ownerKey.'_'.$roleKey;

                    // Guards against this seeder ever drifting away from the
                    // duplicate-email exception list used by the rest of the app.
                    if (! DemoAccounts::isDemoUsername($username)) {
                        throw new \LogicException("Demo username [{$username}] is not registered in ".DemoAccounts::class);
                    }

                    $scope = $this->resolveScope($role['reference'], $role['fallback']);

                    DB::table('ra_users')->updateOrInsert(
                        ['hr_username' => $username],
                        [
                            'hr_first_name' => $owner['first'],
                            'hr_last_name' => $owner['last'].' '.ucfirst($roleKey),
                            'hr_email_address' => $owner['email'],
                            'hr_password' => $hashedPassword,
                            'hr_info' => 'Dedicated client demo account; sign in with username + password.',
                            'hr_user_level' => $scope['hr_user_level'],
                            'hr_signup_date' => $now->format('Y-m-d H:i:s'),
                            'activated' => '1',
                            'upload_no' => 1000,
                            'totalpost' => 0,
                            'groupid' => $scope['groupid'],
                            'm_group' => 1,
                            'status' => 3,
                            'companies_groups_id' => $scope['companies_groups_id'],
                            'branch_id' => $scope['branch_id'],
                            'failed_login_attempts' => 0,
                            'last_failed_login' => null,
                            'isSearchedField' => '1',
                            'lastPassChange' => (string) time(),
                            'department_supervisor' => '0',
                            // Email-only OTP: no mobile, so the SMS channel is never selected.
                            'mobile' => null,
                            'idno' => null,
                            'updated_at' => $now,
                        ],
                    );
                }
            }

            $this->syncBranchGrants();
        });

        $this->command?->info('Client demo login accounts are ready (8 accounts).');
    }

    /**
     * @param  array{hr_user_level:string,branch_id:int,groupid:int,companies_groups_id:int}  $fallback
     * @return array{hr_user_level:string,branch_id:int,groupid:int,companies_groups_id:int}
     */
    private function resolveScope(string $referenceUsername, array $fallback): array
    {
        $reference = DB::table('ra_users')
            ->where('hr_username', $referenceUsername)
            ->first(['hr_user_level', 'branch_id', 'groupid', 'companies_groups_id']);

        if ($reference === null) {
            return $fallback;
        }

        return [
            'hr_user_level' => (string) $reference->hr_user_level,
            'branch_id' => (int) $reference->branch_id,
            'groupid' => (int) $reference->groupid,
            'companies_groups_id' => (int) $reference->companies_groups_id,
        ];
    }

    /**
     * Mirrors each branch reference account's direct page grants onto the
     * matching demo accounts, so branch scope is identical to the audit users
     * without depending on local `user_permission` IDs.
     */
    private function syncBranchGrants(): void
    {
        if (! Schema::hasTable('user_permission')) {
            return;
        }

        foreach (['brancha' => 'PW_AUDIT_BRANCH_A', 'branchb' => 'PW_AUDIT_BRANCH_B'] as $roleKey => $referenceUsername) {
            $referenceId = DB::table('ra_users')->where('hr_username', $referenceUsername)->value('hr_id');

            if ($referenceId === null) {
                continue;
            }

            $grants = DB::table('user_permission')
                ->where('userid', (int) $referenceId)
                ->get(['page', 'permit']);

            foreach (array_keys(self::OWNERS) as $ownerKey) {
                $userId = DB::table('ra_users')->where('hr_username', $ownerKey.'_'.$roleKey)->value('hr_id');

                if ($userId === null) {
                    continue;
                }

                foreach ($grants as $grant) {
                    $row = ['userid' => (int) $userId, 'page' => $grant->page, 'permit' => $grant->permit];

                    if (! DB::table('user_permission')->where($row)->exists()) {
                        DB::table('user_permission')->insert($row);
                    }
                }
            }
        }
    }
}
