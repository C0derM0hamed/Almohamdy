<?php

namespace Tests\Feature;

use App\Models\GovAccount;
use App\Models\GovAccountNotice;
use App\Models\GovAccountNoticeRecipient;
use App\Models\GovAccountRequest;
use App\Services\GovAccounts\GovAccountDashboardService;
use Database\Seeders\GovAccountReferenceSeeder;
use Illuminate\Support\Facades\DB;

class GovAccountDashboardTest extends GovAccountModuleTestCase
{
    private int $authorityId;

    private int $serviceId;

    private int $roleId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GovAccountReferenceSeeder::class);
        $this->authorityId = (int) DB::table('gov_account_authorities')->where('companies_groups_id', 1)->value('id');
        $this->serviceId = (int) DB::table('gov_account_services')->where('companies_groups_id', 1)->value('id');
        $this->roleId = (int) DB::table('gov_account_roles')->where('companies_groups_id', 1)->value('id');
        $this->grant(10, 'gov_accounts.view');
    }

    public function test_dashboard_kpis_use_account_request_and_company_notice_scopes(): void
    {
        $this->makeAccount(11, 1, 1, 'active');
        $this->makeAccount(11, 1, 1, 'suspended');
        $this->makeAccount(11, 2, 2, 'active');
        GovAccountRequest::query()->create($this->requestAttributes(11, 1, 1, 'close', 'under_review'));

        $notice = $this->makeNotice(1);
        GovAccountNoticeRecipient::query()->create(['notice_id' => $notice->id, 'user_id' => 11, 'email' => 'user11@example.test', 'token' => str_repeat('a', 64), 'sent_at' => now(), 'viewed_at' => now(), 'view_count' => 1, 'last_viewed_at' => now()]);
        GovAccountNoticeRecipient::query()->create(['notice_id' => $notice->id, 'user_id' => 10, 'email' => 'user10@example.test', 'token' => str_repeat('b', 64), 'sent_at' => now()]);
        $this->makeNotice(2);

        $this->actAsGovAccountUser(10);
        session(['locale' => 'en']);
        $metrics = app(GovAccountDashboardService::class)->metrics();
        $this->assertSame(2, $metrics['accounts']['total']);
        $this->assertSame(1, $metrics['accounts']['active']);
        $this->assertSame(1, $metrics['accounts']['suspended']);
        $this->assertSame(1, $metrics['multi_account_employees']);
        $this->assertSame(3, $metrics['requests']['total']);
        $this->assertSame(1, $metrics['requests_by_type']['close']);
        $this->assertSame(['sent' => 1, 'recipients' => 2, 'viewed' => 1, 'view_rate' => 50.0], $metrics['notices']);
        $this->get(route('modules.gov-accounts.dashboard'))->assertOk()->assertSee('Official Accounts Dashboard')->assertSee('50%');
    }

    public function test_dashboard_requires_view_or_process_permission(): void
    {
        $this->actAsGovAccountUser(11);
        $this->get(route('modules.gov-accounts.dashboard'))->assertForbidden();
    }

    public function test_account_and_request_filters_work_and_view_only_users_do_not_see_mutating_actions(): void
    {
        $matching = $this->makeAccount(11, 1, 1, 'active');
        $excluded = $this->makeAccount(10, 1, 1, 'suspended');
        $review = GovAccountRequest::query()->create($this->requestAttributes(11, 1, 1, 'close', 'under_review'));

        $this->actAsGovAccountUser(10);
        session(['locale' => 'en']);

        $this->get(route('modules.gov-accounts.accounts.index', ['employee_user_id' => 11, 'status' => 'active']))
            ->assertOk()->assertSee($matching->username)->assertDontSee($excluded->username);
        $this->get(route('modules.gov-accounts.requests.index', ['created_by' => 10, 'department_id' => 1, 'date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]))
            ->assertOk()->assertSee('name="created_by"', false)->assertSee((string) $review->id);
        $this->get(route('modules.gov-accounts.accounts.show', $matching))
            ->assertOk()->assertDontSee('Create lifecycle request');
        $this->get(route('modules.gov-accounts.requests.show', $review))
            ->assertOk()
            ->assertDontSee('action="'.route('modules.gov-accounts.requests.approve', $review).'"', false)
            ->assertDontSee('action="'.route('modules.gov-accounts.requests.reject', $review).'"', false);
    }

    private function makeAccount(int $employeeId, int $branchId, int $departmentId, string $status): GovAccount
    {
        $request = GovAccountRequest::query()->create($this->requestAttributes($employeeId, $branchId, $departmentId));

        return GovAccount::query()->create(['companies_groups_id' => 1, 'branch_id' => $branchId, 'employee_user_id' => $employeeId, 'authority_id' => $this->authorityId, 'service_id' => $this->serviceId, 'role_id' => $this->roleId, 'username' => uniqid('ACC-', true), 'status' => $status, 'created_from_request_id' => $request->id, 'account_created_at' => '2026-08-01']);
    }

    private function requestAttributes(int $employeeId, int $branchId, int $departmentId, string $type = 'create', string $status = 'completed'): array
    {
        return ['companies_groups_id' => 1, 'branch_id' => $branchId, 'type' => $type, 'status' => $status, 'origin' => 'department', 'employee_user_id' => $employeeId, 'department_id' => $departmentId, 'authority_id' => $this->authorityId, 'service_id' => $this->serviceId, 'role_id' => $this->roleId, 'justification' => 'Dashboard fixture', 'round' => 1, 'created_by' => 10];
    }

    private function makeNotice(int $companyId): GovAccountNotice
    {
        return GovAccountNotice::query()->create(['companies_groups_id' => $companyId, 'title' => 'Training', 'authority_id' => $this->authorityId, 'description' => 'Training notice', 'event_date' => '2026-09-15', 'event_time' => '10:00', 'attendance_method' => 'online', 'targeting' => ['mode' => 'all', 'ids' => []], 'created_by' => 10, 'sent_at' => now(), 'publish' => true]);
    }
}
