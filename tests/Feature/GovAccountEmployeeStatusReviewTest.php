<?php

namespace Tests\Feature;

use App\Models\GovAccount;
use App\Models\GovAccountRequest;
use App\Models\User;
use App\Services\GovAccounts\EmployeeStatusProviderInterface;
use Database\Seeders\GovAccountReferenceSeeder;
use Illuminate\Support\Facades\DB;

class GovAccountEmployeeStatusReviewTest extends GovAccountModuleTestCase
{
    public function test_disabled_null_provider_seam_does_nothing(): void
    {
        $this->artisan('hm:gov-accounts-review-employee-status')
            ->expectsOutput('Government Accounts employee-status review is disabled.')
            ->assertSuccessful();
        $this->assertInstanceOf(EmployeeStatusProviderInterface::class, app(EmployeeStatusProviderInterface::class));
        $this->assertDatabaseCount('gov_account_notifications', 0);
    }

    public function test_fake_provider_flags_inactive_employee_once_without_changing_account(): void
    {
        $this->seed(GovAccountReferenceSeeder::class);
        DB::table('ra_users')->insert(['hr_id' => 12, 'hr_first_name' => 'HR User', 'hr_email_address' => 'hr@example.test', 'hr_username' => 'hr12', 'hr_password' => 'unused', 'hr_user_level' => 1, 'branch_id' => 1, 'companies_groups_id' => 1, 'groupid' => 12, 'activated' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->grant(10, 'gov_accounts.process');
        $this->grant(12, 'gov_accounts.hr');
        $authorityId = (int) DB::table('gov_account_authorities')->where('companies_groups_id', 1)->value('id');
        $serviceId = (int) DB::table('gov_account_services')->where('companies_groups_id', 1)->value('id');
        $roleId = (int) DB::table('gov_account_roles')->where('companies_groups_id', 1)->value('id');
        $request = GovAccountRequest::query()->create(['companies_groups_id' => 1, 'branch_id' => 1, 'type' => 'create', 'status' => 'completed', 'origin' => 'department', 'employee_user_id' => 11, 'department_id' => 1, 'authority_id' => $authorityId, 'service_id' => $serviceId, 'role_id' => $roleId, 'justification' => 'HR seam fixture', 'round' => 1, 'created_by' => 10]);
        $account = GovAccount::query()->create(['companies_groups_id' => 1, 'branch_id' => 1, 'employee_user_id' => 11, 'authority_id' => $authorityId, 'service_id' => $serviceId, 'role_id' => $roleId, 'username' => 'ACTIVE-ACCOUNT', 'status' => 'active', 'created_from_request_id' => $request->id, 'account_created_at' => '2026-08-01']);

        $fake = new class implements EmployeeStatusProviderInterface
        {
            public function isActive(User $employee): ?bool
            {
                return $employee->getKey() === 11 ? false : true;
            }
        };
        $this->app->instance(EmployeeStatusProviderInterface::class, $fake);
        config(['hm.gov_accounts.employee_status.enabled' => true]);

        $this->artisan('hm:gov-accounts-review-employee-status')->assertSuccessful();
        $this->assertDatabaseHas('gov_accounts', ['id' => $account->id, 'status' => 'active', 'closed_at' => null]);
        $this->assertSame(2, DB::table('gov_account_notifications')->where('account_id', $account->id)->where('event_type', 'employee_status_action_required')->where('status', 'action_required')->count());
        $this->assertSame([10, 12], DB::table('gov_account_notifications')->where('account_id', $account->id)->orderBy('recipient_user_id')->pluck('recipient_user_id')->map(fn ($id): int => (int) $id)->all());

        $this->artisan('hm:gov-accounts-review-employee-status')->assertSuccessful();
        $this->assertSame(2, DB::table('gov_account_notifications')->where('account_id', $account->id)->where('event_type', 'employee_status_action_required')->count());
        $this->assertDatabaseHas('gov_accounts', ['id' => $account->id, 'status' => 'active']);
    }
}
