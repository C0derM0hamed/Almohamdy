<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Synchronises the legacy authorization catalog used by NewProject.
 *
 * This deliberately only inserts/updates authorization rows. It never deletes
 * users, roles, permissions, groups, or business records.
 */
class AuthorizationSeeder extends Seeder
{
    /** @var list<array{role_id:int,role_name:string,branch_id:int,publish:string}> */
    private const ROLES = [
        [1, 'منسق تدريب', 0, '1'],
        [2, 'إدارة التدريب', 0, '1'],
        [3, ' إتفاقية تقديم خدمات طبية  يقين', 0, '1'],
        [4, ' إتفاقية تقديم خدمات طبية  يدوي', 0, '1'],
        [5, 'مشاهدة و تعديل إقرار التدريب الخاص', 0, '1'],
        [6, 'مشاهدة و تعديل إقرار التدريب العام', 0, '1'],
        [7, 'إنشاء الخطابات الصادرة الى الجهات', 0, '1'],
        [8, 'تأكيد خطابات علاقات المرضى', 12, '0'],
        [9, 'تعميد  الدعاوى القانونية', 2, '0'],
    ];

    /** @var list<array{perm_id:int,perm_desc:string,branch_id:int}> */
    private const PERMISSIONS = [
        [1, 'view training_confirmation_coordinator', 0],
        [2, 'create training_confirmation_coordinator', 0],
        [3, 'edit training_confirmation_coordinator', 0],
        [4, 'delete training_confirmation_coordinator', 0],
        [5, 'view training_confirmation', 0],
        [6, 'Medical Services Provision Agreement non Yaqeen', 0],
        [7, 'Medical Services Provision Agreement Yaqeen', 0],
        [8, 'View the general training declaration', 0],
        [9, 'View the private training declaration', 0],
        [10, 'view corporate_communications_outgoing_letters', 0],
        [11, 'view complaints_confirm', 12],
        [12, 'view lawsuitApproval', 0],
    ];

    /** @var list<array{role_id:int,perm_id:int}> */
    private const ROLE_PERMISSIONS = [
        [1, 1], [1, 2], [1, 3], [1, 4], [1, 5],
        [2, 5], [3, 7], [4, 6], [5, 9], [6, 8], [7, 10], [8, 11], [9, 12],
    ];

    /** Existing permission group used by PERMISSION ADMIN (groupid = 1). */
    private const PERMISSION_ADMIN_GROUP = [
        'adm_country', 'adm_reg_branch', 'adm_user_branch', 'change_my_pass',
        'change_user_pass', 'city', 'currencies', 'order_status',
        'transactions_methods', 'update_my_informations', 'users',
        'user_groups_permissins',
    ];

    /** Existing direct grants for both branch audit accounts. */
    private const BRANCH_AUDIT_GRANTS = [
        'corporate_communications',
        'corporate_communications_outgoing_letters',
        'government_circulars',
        'government_inspection_visits',
        'Governmentـreportss',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::ROLES as [$id, $name, $branch, $publish]) {
                DB::table('roles')->updateOrInsert(
                    ['role_id' => $id],
                    ['role_name' => $name, 'branch_id' => $branch, 'publish' => $publish],
                );
            }

            foreach (self::PERMISSIONS as [$id, $description, $branch]) {
                DB::table('permissions')->updateOrInsert(
                    ['perm_id' => $id],
                    ['perm_desc' => $description, 'branch_id' => $branch],
                );
            }

            foreach (self::ROLE_PERMISSIONS as [$roleId, $permissionId]) {
                $this->ensureRow('role_perm', ['role_id' => $roleId, 'perm_id' => $permissionId]);
            }

            foreach (self::PERMISSION_ADMIN_GROUP as $page) {
                $this->ensureRow('user_groups_permission', [
                    'groupid' => 1, 'page' => $page, 'permit' => '2',
                ]);
            }

            foreach (['PW_AUDIT_BRANCH_A', 'PW_AUDIT_BRANCH_B', 'CLIENT_TEST_BRANCH_A', 'CLIENT_TEST_BRANCH_B'] as $username) {
                $userId = DB::table('ra_users')->where('hr_username', $username)->value('hr_id');
                if ($userId === null) {
                    continue;
                }

                foreach (self::BRANCH_AUDIT_GRANTS as $page) {
                    $this->ensureRow('user_permission', [
                        'userid' => (int) $userId, 'page' => $page, 'permit' => '2',
                    ]);
                }
            }
        });
    }

    /** @param array<string, int|string> $where */
    private function ensureRow(string $table, array $where): void
    {
        if (! DB::table($table)->where($where)->exists()) {
            DB::table($table)->insert($where);
        }
    }
}
