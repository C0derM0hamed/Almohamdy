<?php

namespace Tests\Feature;

use App\Services\DepartmentPerformanceReport\DepartmentPerformanceReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DepartmentPerformanceReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');

        Schema::create('ra_users', function (Blueprint $table): void {
            $table->increments('hr_id'); $table->string('hr_username')->nullable(); $table->string('hr_first_name')->nullable(); $table->string('hr_last_name')->nullable();
            $table->integer('hr_user_level')->default(1); $table->integer('branch_id')->default(2); $table->integer('companies_groups_id')->default(1);
        });
        Schema::create('duty_period', function (Blueprint $table): void { $table->increments('id'); $table->string('name_ar')->nullable(); $table->string('name_en')->nullable(); $table->integer('publish')->default(1); });
        Schema::create('branches', function (Blueprint $table): void { $table->increments('id'); $table->string('name_ar')->nullable(); $table->string('name_en')->nullable(); $table->integer('publish')->default(1); });
        Schema::create('companies_groups', function (Blueprint $table): void { $table->increments('id'); $table->string('name_ar')->nullable(); $table->string('name_en')->nullable(); $table->integer('publish')->default(1); });
        Schema::create('branches_departments', function (Blueprint $table): void { $table->increments('id'); $table->string('name_ar')->nullable(); $table->string('name_en')->nullable(); $table->integer('publish')->default(1); });
        foreach (['action_taken', 'rep2_amont_due_reasons', 'rep2_amount_due_action', 'rep2_not_signing_pledge_to_pay_reasons', 'rep3_payer_name_type', 'rep3_request_type', 'rep3_objection_court_direction', 'rep3_orders_type'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void { $table->increments('id'); $table->string('name_ar')->nullable(); $table->string('name_en')->nullable(); $table->integer('publish')->default(1); });
        }

        Schema::create('report_2', function (Blueprint $table): void {
            $table->increments('id'); $table->integer('period'); $table->string('date'); $table->integer('branch_id');
            foreach (['patients_have_remaining_money_inpatient', 'employees_revenue_shortfall_outpatient', 'employees_revenue_shortfall_emergency', 'employees_revenue_shortfall_hospitalization_office', 'directors_revenue_deficit', 'total_remaining_for_patients_hospitalization_department', 'total_revenue_deficit_outpatient_department', 'total_revenue_shortfall_emergency_department', 'total_revenue_deficit_hypnotherapy_office', 'total_revenue_shortfall_emergency_managers'] as $column) $table->string($column)->default('0');
            $table->integer('companies_groups_id'); $table->timestamp('created_at')->nullable(); $table->integer('creator')->default(0);
        });
        Schema::create('report_2_revenue_deficit', function (Blueprint $table): void { $table->increments('id'); $table->integer('report_id'); $table->string('date'); $table->integer('branch_id'); $table->integer('emp_id'); $table->integer('branchid'); $table->string('amount'); $table->string('other')->nullable(); $table->string('action'); });
        Schema::create('report_2_patients_bond_signed', function (Blueprint $table): void { $table->increments('id'); $table->integer('report_id'); $table->string('date'); $table->integer('branch_id'); $table->string('p_name'); $table->string('filenumber7'); $table->string('idno'); $table->string('payer_name'); $table->string('payer_name_idno'); $table->string('bond_amount'); $table->string('file')->nullable(); });
        Schema::create('report_2_previous_paid', function (Blueprint $table): void { $table->increments('id'); $table->integer('report_id'); $table->string('date'); $table->integer('branch_id'); $table->string('patient_name'); $table->string('filenumber5'); $table->string('patient_idno'); $table->string('total_amount'); $table->string('paid_amount')->nullable(); $table->string('rest_amount'); $table->string('invoce_no'); $table->string('files')->nullable(); });

        Schema::create('report_3', function (Blueprint $table): void { $table->increments('id'); $table->integer('period'); $table->string('date'); $table->integer('branch_id'); $table->integer('cases_filed_in_court'); $table->integer('total_requests_Najiz'); $table->integer('pending_cases'); $table->integer('companies_groups_id'); $table->timestamp('created_at')->nullable(); $table->integer('creator')->default(0); });
        Schema::create('report_3_pending_claims_report', function (Blueprint $table): void { $table->increments('id'); $table->integer('report_id'); $table->string('date'); $table->integer('branch_id'); $table->string('case_number'); $table->string('filenumber5'); $table->string('session_date'); $table->string('respondent'); $table->string('respondent_idno'); $table->string('orders')->nullable(); $table->string('file')->nullable(); });

        DB::table('ra_users')->insert(['hr_id' => 10, 'hr_username' => 'collector', 'hr_first_name' => 'محصل', 'hr_last_name' => 'التقرير', 'hr_user_level' => 1, 'branch_id' => 2, 'companies_groups_id' => 1]);
        DB::table('duty_period')->insert(['id' => 1, 'name_ar' => 'الصباحية', 'name_en' => 'Morning']);
        DB::table('branches')->insert(['id' => 5, 'name_ar' => 'التنويم', 'name_en' => 'Inpatient']);
        DB::table('companies_groups')->insert(['id' => 1, 'name_ar' => 'النزهة', 'name_en' => 'Nuzha']);
        DB::table('branches_departments')->insert(['id' => 7, 'name_ar' => 'الطوارئ', 'name_en' => 'Emergency']);
        DB::table('action_taken')->insert(['id' => 2, 'name_ar' => 'تم التواصل', 'name_en' => 'Contacted']);
        DB::table('rep3_payer_name_type')->insert(['id' => 1, 'name_ar' => 'فرد', 'name_en' => 'Individual']);

        session(['hr_user_id' => 99, 'hr_user_level' => 3, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
    }

    public function test_collection_report_filters_scope_sums_metrics_and_decorates_rows(): void
    {
        app()->setLocale('ar');

        DB::table('report_2')->insert([
            ['id' => 1, 'period' => 1, 'date' => (string) strtotime('2026-08-01'), 'branch_id' => 2, 'patients_have_remaining_money_inpatient' => '4', 'employees_revenue_shortfall_outpatient' => '2', 'employees_revenue_shortfall_emergency' => '0', 'employees_revenue_shortfall_hospitalization_office' => '0', 'directors_revenue_deficit' => '1', 'total_remaining_for_patients_hospitalization_department' => '500', 'total_revenue_deficit_outpatient_department' => '100', 'total_revenue_shortfall_emergency_department' => '0', 'total_revenue_deficit_hypnotherapy_office' => '0', 'total_revenue_shortfall_emergency_managers' => '0', 'companies_groups_id' => 1, 'creator' => 10],
            ['id' => 2, 'period' => 1, 'date' => (string) strtotime('2026-08-01'), 'branch_id' => 2, 'patients_have_remaining_money_inpatient' => '99', 'employees_revenue_shortfall_outpatient' => '99', 'employees_revenue_shortfall_emergency' => '99', 'employees_revenue_shortfall_hospitalization_office' => '99', 'directors_revenue_deficit' => '99', 'total_remaining_for_patients_hospitalization_department' => '99', 'total_revenue_deficit_outpatient_department' => '99', 'total_revenue_shortfall_emergency_department' => '99', 'total_revenue_deficit_hypnotherapy_office' => '99', 'total_revenue_shortfall_emergency_managers' => '99', 'companies_groups_id' => 2, 'creator' => 10],
        ]);
        DB::table('report_2_revenue_deficit')->insert(['id' => 1, 'report_id' => 1, 'date' => (string) strtotime('2026-08-01'), 'branch_id' => 2, 'emp_id' => 10, 'branchid' => 7, 'amount' => '50', 'other' => 'ملاحظة', 'action' => '2']);

        $service = app(DepartmentPerformanceReportService::class);
        $result = $service->report('collection', $service->filters(['from' => '2026-08-01', 'to' => '2026-08-31', 'submitted' => true]));

        $this->assertSame([1], $result['reportIds']);
        $this->assertSame(4.0, $result['summary'][0]['total']);
        $this->assertSame('collector محصل التقرير', $result['sections']->get('revenue-deficit')['rows']->first()->creator_label);
        $this->assertSame('الطوارئ', $result['sections']->get('revenue-deficit')['rows']->first()->department_label);
    }

    public function test_legal_report_attachment_is_parent_and_company_scoped(): void
    {
        DB::table('report_3')->insert([
            ['id' => 1, 'period' => 1, 'date' => (string) strtotime('2026-08-01'), 'branch_id' => 3, 'cases_filed_in_court' => 2, 'total_requests_Najiz' => 3, 'pending_cases' => 1, 'companies_groups_id' => 1, 'creator' => 10],
            ['id' => 2, 'period' => 1, 'date' => (string) strtotime('2026-08-01'), 'branch_id' => 3, 'cases_filed_in_court' => 90, 'total_requests_Najiz' => 90, 'pending_cases' => 90, 'companies_groups_id' => 2, 'creator' => 10],
        ]);
        DB::table('report_3_pending_claims_report')->insert(['id' => 1, 'report_id' => 1, 'date' => (string) strtotime('2026-08-01'), 'branch_id' => 3, 'case_number' => 'C-1', 'filenumber5' => 'F-1', 'session_date' => (string) strtotime('2026-08-15'), 'respondent' => 'طرف', 'respondent_idno' => '1', 'orders' => 'طلب', 'file' => 'legal.pdf']);

        $service = app(DepartmentPerformanceReportService::class);
        $result = $service->report('legal', $service->filters(['from' => '2026-08-01', 'to' => '2026-08-31', 'submitted' => true]));

        $this->assertSame([1], $result['reportIds']);
        $this->assertSame(3.0, $result['summary'][1]['total']);
        $this->assertSame('legal.pdf', $service->attachment('legal', 'pending-claims', 1)->file);
        $this->assertNull($service->attachment('legal', 'pending-claims', 999));
    }
}
