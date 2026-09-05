<?php

namespace Tests\Feature;

use App\Models\GovAccountRequest;
use Illuminate\Support\Facades\DB;

class GovAccountHrTest extends GovAccountLifecycleTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->grant(11, 'gov_accounts.hr');
    }

    public function test_hr_searches_open_accounts_and_can_raise_only_suspend_or_close(): void
    {
        $account = $this->makeAccount();
        $this->actAsGovAccountUser(11);
        $html = $this->get(route('modules.gov-accounts.hr.index', ['search' => 'Employee']))
            ->assertOk()
            ->assertSee('ACC-11')
            ->assertSee('id="govHrLifecycleModal"', false)
            ->assertSee(__('gov_accounts.fields.employee'))
            ->getContent();
        $this->assertDoesNotMatchRegularExpression('/<td>\s*<form method="POST"/', $html);
        $this->post(route('modules.gov-accounts.hr.requests.store', $account), ['type' => 'close', 'justification' => 'Employee contract ended'])->assertRedirect();
        $request = GovAccountRequest::query()->where('account_id', $account->id)->where('origin', 'hr')->firstOrFail();
        $this->assertSame('close', $request->type);
        $this->assertDatabaseHas('gov_accounts', ['id' => $account->id, 'status' => 'closure_requested']);

        $this->post(route('modules.gov-accounts.hr.requests.store', $account), ['type' => 'modify', 'justification' => 'Not allowed'])->assertForbidden();
    }

    public function test_hr_is_branch_and_company_scoped_and_cannot_access_foreign_accounts(): void
    {
        $account = $this->makeAccount();
        $foreignSource = DB::table('gov_account_requests')->insertGetId(['companies_groups_id' => 2, 'branch_id' => 3, 'type' => 'create', 'status' => 'completed', 'origin' => 'department', 'employee_user_id' => 40, 'department_id' => 3, 'authority_id' => $this->authorityId, 'service_id' => $this->serviceId, 'role_id' => $this->roleId, 'justification' => 'Foreign', 'round' => 1, 'created_by' => 40, 'created_at' => now(), 'updated_at' => now()]);
        $foreign = DB::table('gov_accounts')->insertGetId(['companies_groups_id' => 2, 'branch_id' => 3, 'employee_user_id' => 40, 'authority_id' => $this->authorityId, 'service_id' => $this->serviceId, 'role_id' => $this->roleId, 'username' => 'FOREIGN', 'status' => 'active', 'created_from_request_id' => $foreignSource, 'account_created_at' => '2026-08-01', 'created_at' => now(), 'updated_at' => now()]);

        $this->actAsGovAccountUser(11);
        $this->get(route('modules.gov-accounts.hr.index'))->assertOk()->assertSee($account->username)->assertDontSee('FOREIGN');
        $this->post(route('modules.gov-accounts.hr.requests.store', $foreign), ['type' => 'close', 'justification' => 'Cross scope'])->assertNotFound();
    }
}
