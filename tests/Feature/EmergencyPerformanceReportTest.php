<?php

namespace Tests\Feature;

use App\Services\EmergencyPerformanceReport\EmergencyPerformanceReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmergencyPerformanceReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['report_1', 'employees_attendance', 'ra_users', 'duty_period', 'non_approval_ehala_1', 'report_1_patient', 'support_services_1', 'branches_departments', 'rep1_ehala_non_approval_reason', 'rep1_hospitalization_place', 'rep1_suspension_reasons', 'rep1_payment_type', 'rep1_payment_due_reason', 'rep1_payment_due_action', 'rep1_not_signed_commitment_reason', 'rep1_death_pending_reason', 'rep1_death_pending_action', 'rep1_more_than_two_hours_reasons', 'rep1_more_than_two_hours_action', 'rep1_keeping_body_in_mortuary', 'emergency_pending_approvals_1', 'amounts_due_1', 'patients_signed_1', 'visual_screening_form_1', 'death_pending_1', 'more_than_two_hour_1', 'revenue_deficit_1', 'dead_body'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('ra_users', function (Blueprint $table): void {
            $table->increments('hr_id'); $table->string('hr_username')->nullable(); $table->string('hr_first_name')->nullable(); $table->string('hr_last_name')->nullable(); $table->integer('hr_user_level')->default(1); $table->integer('branch_id')->default(1); $table->integer('companies_groups_id')->default(1); $table->integer('activated')->default(1);
        });
        Schema::create('duty_period', function (Blueprint $table): void { $table->increments('id'); $table->string('name_ar')->nullable(); $table->string('name_en')->nullable(); $table->integer('publish')->default(1); });
        Schema::create('report_1', function (Blueprint $table): void { $table->increments('id'); $table->integer('period'); $table->integer('date'); $table->integer('branch_id'); $table->integer('inpatient')->default(0); $table->integer('inpatient_insurance')->default(0); $table->integer('red_crescent')->default(0); $table->integer('emergency')->default(0); $table->integer('efada_approval')->default(0); $table->integer('efada_non_approval')->default(0); $table->integer('companies_groups_id'); $table->integer('creator'); });
        Schema::create('employees_attendance', function (Blueprint $table): void { $table->increments('id'); $table->integer('report_id'); $table->integer('branch_id'); $table->integer('companies_groups_id'); $table->integer('attendees')->default(0); $table->integer('absence')->default(0); $table->integer('latecomers')->default(0); $table->integer('permissible')->default(0); });
        foreach (['non_approval_ehala_1', 'report_1_patient', 'support_services_1', 'emergency_pending_approvals_1', 'amounts_due_1', 'patients_signed_1', 'visual_screening_form_1', 'death_pending_1', 'more_than_two_hour_1', 'revenue_deficit_1', 'dead_body'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void { $table->increments('id'); $table->integer('report_id'); $table->integer('branch_id'); $table->integer('date')->default(0); $table->string('file')->nullable(); $table->string('files')->nullable(); });
        }
        Schema::create('branches_departments', function (Blueprint $table): void { $table->increments('id'); $table->string('name_ar')->nullable(); $table->string('name_en')->nullable(); });
        foreach (['rep1_ehala_non_approval_reason', 'rep1_hospitalization_place', 'rep1_suspension_reasons', 'rep1_payment_type', 'rep1_payment_due_reason', 'rep1_payment_due_action', 'rep1_not_signed_commitment_reason', 'rep1_death_pending_reason', 'rep1_death_pending_action', 'rep1_more_than_two_hours_reasons', 'rep1_more_than_two_hours_action', 'rep1_keeping_body_in_mortuary'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void { $table->increments('id'); $table->string('name_ar')->nullable(); $table->string('name_en')->nullable(); });
        }

        DB::table('ra_users')->insert(['hr_id' => 10, 'hr_username' => 'auditor', 'hr_first_name' => 'مدقق', 'hr_last_name' => 'التدقيق', 'hr_user_level' => 3, 'branch_id' => 1, 'companies_groups_id' => 1]);
        DB::table('duty_period')->insert(['id' => 1, 'name_ar' => 'الصباحية', 'name_en' => 'Morning']);
        DB::table('report_1')->insert([
            ['id' => 1, 'period' => 1, 'date' => strtotime('2021-06-01'), 'branch_id' => 1, 'inpatient' => 4, 'inpatient_insurance' => 3, 'red_crescent' => 2, 'emergency' => 1, 'efada_approval' => 5, 'efada_non_approval' => 1, 'companies_groups_id' => 1, 'creator' => 10],
            ['id' => 2, 'period' => 1, 'date' => strtotime('2021-06-02'), 'branch_id' => 1, 'inpatient' => 99, 'inpatient_insurance' => 0, 'red_crescent' => 0, 'emergency' => 0, 'efada_approval' => 0, 'efada_non_approval' => 0, 'companies_groups_id' => 2, 'creator' => 10],
        ]);
        DB::table('employees_attendance')->insert(['report_id' => 1, 'branch_id' => 1, 'companies_groups_id' => 1, 'attendees' => 7, 'absence' => 2, 'latecomers' => 1, 'permissible' => 3]);
        DB::table('non_approval_ehala_1')->insert(['id' => 1, 'report_id' => 1, 'branch_id' => 1, 'date' => strtotime('2021-06-01'), 'file' => 'audit.txt']);
    }

    public function test_report_is_company_scoped_and_attachment_is_parent_scoped(): void
    {
        session(['hr_user_id' => 10, 'hr_user_level' => 3, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
        $service = app(EmergencyPerformanceReportService::class);
        $result = $service->report($service->filters(['from' => '2021-01-01', 'to' => '2021-12-31', 'submitted' => true]));

        $this->assertSame([1], $result['reportIds']);
        $this->assertSame(7, $result['attendance']['attendees']);
        $this->assertSame(4, (int) $result['reports']->sum('inpatient'));
        $this->assertSame(1, $result['sections']->get('non-approval')['rows']->count());
        $this->assertSame('audit.txt', $service->attachment('non-approval', 1)->file);
        $this->assertNull($service->attachment('non-approval', 999));
    }
}
