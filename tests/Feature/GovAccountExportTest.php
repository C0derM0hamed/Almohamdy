<?php

namespace Tests\Feature;

use App\Models\GovAccount;
use App\Models\GovAccountNotice;
use App\Models\GovAccountNoticeRecipient;
use App\Models\GovAccountRequest;
use Database\Seeders\GovAccountReferenceSeeder;
use Illuminate\Support\Facades\DB;

class GovAccountExportTest extends GovAccountModuleTestCase
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
        $this->grant(10, 'gov_accounts.export');
    }

    public function test_account_and_request_csv_xls_exports_are_branch_scoped_and_filterable(): void
    {
        $this->makeAccount(11, 1, 1, 'ACC-VISIBLE');
        $this->makeAccount(11, 2, 2, 'ACC-HIDDEN');
        GovAccountRequest::query()->create($this->requestAttributes(11, 1, 1, 'suspend', 'under_review'));
        $this->actAsGovAccountUser(10);
        session(['locale' => 'en']);

        $csv = $this->get(route('modules.gov-accounts.export', ['format' => 'csv', 'report' => 'accounts']));
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $csv->streamedContent();
        $this->assertStringContainsString('ACC-VISIBLE', $content);
        $this->assertStringNotContainsString('ACC-HIDDEN', $content);

        $requests = $this->get(route('modules.gov-accounts.export', ['format' => 'xls', 'report' => 'requests', 'type' => 'suspend']));
        $requests->assertOk()->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $xml = $requests->streamedContent();
        $this->assertStringContainsString('<Workbook', $xml);
        $this->assertStringContainsString('Suspend account', $xml);
        $this->assertStringNotContainsString('Create account', $xml);
    }

    public function test_notice_view_export_is_company_scoped_and_export_permission_is_required(): void
    {
        $visible = $this->notice(1, 'VISIBLE NOTICE');
        $hidden = $this->notice(2, 'HIDDEN NOTICE');
        GovAccountNoticeRecipient::query()->create(['notice_id' => $visible->id, 'user_id' => 11, 'email' => 'user11@example.test', 'token' => str_repeat('c', 64), 'sent_at' => now(), 'viewed_at' => now(), 'view_count' => 2, 'last_viewed_at' => now()]);
        GovAccountNoticeRecipient::query()->create(['notice_id' => $hidden->id, 'user_id' => 40, 'email' => 'user40@example.test', 'token' => str_repeat('d', 64), 'sent_at' => now()]);

        $this->actAsGovAccountUser(10);
        session(['locale' => 'en']);
        $response = $this->get(route('modules.gov-accounts.export', ['format' => 'csv', 'report' => 'notices']));
        $content = $response->streamedContent();
        $this->assertStringContainsString('VISIBLE NOTICE', $content);
        $this->assertStringContainsString('Viewed', $content);
        $this->assertStringNotContainsString('HIDDEN NOTICE', $content);

        $this->actAsGovAccountUser(11);
        $this->get(route('modules.gov-accounts.export', ['format' => 'csv']))->assertForbidden();
        $this->get('/modules/gov-accounts/export/pdf')->assertNotFound();
    }

    private function makeAccount(int $employeeId, int $branchId, int $departmentId, string $username): GovAccount
    {
        $request = GovAccountRequest::query()->create($this->requestAttributes($employeeId, $branchId, $departmentId));

        return GovAccount::query()->create(['companies_groups_id' => 1, 'branch_id' => $branchId, 'employee_user_id' => $employeeId, 'authority_id' => $this->authorityId, 'service_id' => $this->serviceId, 'role_id' => $this->roleId, 'username' => $username, 'status' => 'active', 'created_from_request_id' => $request->id, 'account_created_at' => '2026-08-01']);
    }

    private function requestAttributes(int $employeeId, int $branchId, int $departmentId, string $type = 'create', string $status = 'completed'): array
    {
        return ['companies_groups_id' => 1, 'branch_id' => $branchId, 'type' => $type, 'status' => $status, 'origin' => 'department', 'employee_user_id' => $employeeId, 'department_id' => $departmentId, 'authority_id' => $this->authorityId, 'service_id' => $this->serviceId, 'role_id' => $this->roleId, 'justification' => 'Export fixture', 'round' => 1, 'created_by' => 10];
    }

    private function notice(int $companyId, string $title): GovAccountNotice
    {
        return GovAccountNotice::query()->create(['companies_groups_id' => $companyId, 'title' => $title, 'authority_id' => $this->authorityId, 'description' => 'Export notice', 'event_date' => '2026-09-15', 'event_time' => '10:00', 'attendance_method' => 'online', 'targeting' => ['mode' => 'all', 'ids' => []], 'created_by' => 10, 'sent_at' => now(), 'publish' => true]);
    }
}
