<?php

namespace Tests\Feature;

use App\Models\GovAccount;
use App\Models\GovAccountRequest;
use Database\Seeders\GovAccountReferenceSeeder;
use Illuminate\Support\Facades\DB;

class GovAccountLifecycleTest extends GovAccountModuleTestCase
{
    protected int $authorityId;

    protected int $serviceId;

    protected int $secondServiceId;

    protected int $roleId;

    protected int $secondRoleId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GovAccountReferenceSeeder::class);
        $this->authorityId = (int) DB::table('gov_account_authorities')->where('companies_groups_id', 1)->where('name_en', 'Ministry of Health')->value('id');
        $services = DB::table('gov_account_services')->where('companies_groups_id', 1)->where('authority_id', $this->authorityId)->pluck('id');
        $this->serviceId = (int) $services[0];
        $this->secondServiceId = (int) $services[1];
        $roles = DB::table('gov_account_roles')->where('companies_groups_id', 1)->orderBy('id')->pluck('id');
        $this->roleId = (int) $roles[0];
        $this->secondRoleId = (int) $roles[1];
        DB::table('gov_account_department_heads')->insert(['companies_groups_id' => 1, 'department_id' => 1, 'user_id' => 10, 'publish' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->grant(10, 'gov_accounts.request');
        $this->grant(1, 'gov_accounts.process', 'gov_accounts.view');
    }

    public function test_permission_change_reverts_on_reject_then_applies_after_resubmission(): void
    {
        $account = $this->makeAccount();
        $this->actAsGovAccountUser(10);
        $this->post(route('modules.gov-accounts.accounts.requests.store', $account), ['type' => 'permission_change', 'requested_role_id' => $this->secondRoleId, 'justification' => 'Expanded approval duties'])->assertRedirect();
        $request = GovAccountRequest::query()->where('account_id', $account->id)->latest('id')->firstOrFail();
        $this->assertDatabaseHas('gov_accounts', ['id' => $account->id, 'status' => 'modification_requested', 'role_id' => $this->roleId]);

        $this->actAsGovAccountUser(1);
        $this->post(route('modules.gov-accounts.requests.reject', $request), ['reason' => 'Need role confirmation'])->assertRedirect();
        $this->assertDatabaseHas('gov_accounts', ['id' => $account->id, 'status' => 'active', 'role_id' => $this->roleId]);

        $this->actAsGovAccountUser(10);
        $this->post(route('modules.gov-accounts.requests.resubmit', $request), ['response' => 'Role confirmed by department'])->assertRedirect();
        $this->assertDatabaseHas('gov_accounts', ['id' => $account->id, 'status' => 'modification_requested']);

        $this->processToCompletion($request);
        $this->assertDatabaseHas('gov_accounts', ['id' => $account->id, 'status' => 'active', 'role_id' => $this->secondRoleId]);
        $event = DB::table('gov_account_timeline')->where('request_id', $request->id)->where('event_type', 'lifecycle_completed')->first();
        $this->assertStringContainsString('before', (string) $event->meta);
        $this->assertStringContainsString('after', (string) $event->meta);
    }

    public function test_modify_suspend_and_close_are_request_driven_and_never_silent(): void
    {
        $account = $this->makeAccount();
        $modify = $this->createLifecycle($account, ['type' => 'modify', 'service_id' => $this->secondServiceId, 'requested_role_id' => $this->secondRoleId, 'justification' => 'Move to another official service']);
        $this->processToCompletion($modify);
        $this->assertDatabaseHas('gov_accounts', ['id' => $account->id, 'service_id' => $this->secondServiceId, 'role_id' => $this->secondRoleId, 'status' => 'active']);

        $suspend = $this->createLifecycle($account->fresh(), ['type' => 'suspend', 'justification' => 'Employee on extended leave']);
        $this->processToCompletion($suspend);
        $this->assertNotNull(GovAccount::query()->find($account->id)->suspended_at);
        $this->assertDatabaseHas('gov_accounts', ['id' => $account->id, 'status' => 'suspended']);

        $close = $this->createLifecycle($account->fresh(), ['type' => 'close', 'justification' => 'Employment ended']);
        $this->processToCompletion($close);
        $this->assertDatabaseHas('gov_accounts', ['id' => $account->id, 'status' => 'closed', 'closed_reason' => 'Employment ended']);
        $this->assertSame(3, GovAccountRequest::query()->where('account_id', $account->id)->whereIn('type', ['modify', 'suspend', 'close'])->count());
    }

    public function test_processor_cancel_reverts_in_flight_account_status(): void
    {
        $account = $this->makeAccount();
        $request = $this->createLifecycle($account, ['type' => 'suspend', 'justification' => 'Raised in error']);
        $this->assertDatabaseHas('gov_accounts', ['id' => $account->id, 'status' => 'suspension_requested']);

        $this->actAsGovAccountUser(1);
        $this->post(route('modules.gov-accounts.requests.cancel', $request))->assertRedirect();
        $this->assertDatabaseHas('gov_account_requests', ['id' => $request->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('gov_accounts', ['id' => $account->id, 'status' => 'active']);
    }

    protected function makeAccount(int $companyId = 1, int $branchId = 1, int $employeeId = 11): GovAccount
    {
        $source = GovAccountRequest::query()->create(['companies_groups_id' => $companyId, 'branch_id' => $branchId, 'type' => 'create', 'status' => 'completed', 'origin' => 'department', 'employee_user_id' => $employeeId, 'department_id' => $companyId === 1 ? 1 : 3, 'authority_id' => $this->authorityId, 'service_id' => $this->serviceId, 'role_id' => $this->roleId, 'justification' => 'Existing account', 'round' => 1, 'created_by' => 10]);

        return GovAccount::query()->create(['companies_groups_id' => $companyId, 'branch_id' => $branchId, 'employee_user_id' => $employeeId, 'authority_id' => $this->authorityId, 'service_id' => $this->serviceId, 'role_id' => $this->roleId, 'username' => 'ACC-'.$employeeId, 'status' => 'active', 'created_from_request_id' => $source->id, 'managed_by' => 1, 'account_created_at' => '2026-08-01']);
    }

    protected function createLifecycle(GovAccount $account, array $payload): GovAccountRequest
    {
        $this->actAsGovAccountUser(10);
        $this->post(route('modules.gov-accounts.accounts.requests.store', $account), $payload)->assertRedirect();

        return GovAccountRequest::query()->where('account_id', $account->id)->latest('id')->firstOrFail();
    }

    protected function processToCompletion(GovAccountRequest $request): void
    {
        $this->actAsGovAccountUser(1);
        $this->post(route('modules.gov-accounts.requests.approve', $request))->assertRedirect();
        $this->post(route('modules.gov-accounts.requests.authority', $request), ['authority_submitted_at' => '2026-09-01'])->assertRedirect();
        $this->post(route('modules.gov-accounts.requests.complete', $request))->assertRedirect();
    }
}
