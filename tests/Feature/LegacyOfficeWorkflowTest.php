<?php

namespace Tests\Feature;

use App\Http\Controllers\Module\LegacyOffice\LegacyOfficeController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LegacyOfficeWorkflowTest extends TestCase
{
    private LegacyOfficeController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');
        $this->schemas();
        session(['hr_user_id' => 10, 'hr_user_level' => 2, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
        DB::table('holidays_inquiry_decision')->insert([['id' => 1, 'name_ar' => 'تمت الموافقة', 'publish' => 1], ['id' => 2, 'name_ar' => 'مرفوض', 'publish' => 1]]);
        DB::table('medica_report_decision')->insert([['id' => 1, 'name_ar' => 'تمت الموافقة', 'publish' => 1], ['id' => 2, 'name_ar' => 'مرفوض', 'publish' => 1]]);
        DB::table('memo_types')->insert(['id' => 1, 'name_ar' => 'تعميم', 'publish' => 1, 'ranking' => 1]);
        DB::table('service_coverage_memo_types')->insert(['id' => 1, 'name_ar' => 'مطالبة', 'publish' => 1, 'ranking' => 1]);
        DB::table('ra_users')->insert(['hr_id' => 10, 'branch_id' => 1, 'companies_groups_id' => 1, 'activated' => 1, 'isSearchedField' => 1, 'hr_first_name' => 'مدقق', 'hr_last_name' => 'أ']);
        $this->controller = app(LegacyOfficeController::class);
    }

    public function test_holiday_create_and_decision_preserve_company_and_branch_scope(): void
    {
        session(['hr_branch_id' => 5]);
        $request = Request::create('/modules/legacy-office/holidays', 'POST', ['patient_name' => 'مريض', 'nationality' => '102', 'idno' => '1234567890', 'file_number' => 'F1', 'days' => 4, 'issue_date' => '2026-08-01', 'issuer' => 'الموارد البشرية', 'type' => 1]);
        $this->controller->storeHoliday($request);
        $id = (int) DB::table('holidays_inquiry')->value('id');
        $this->controller->decideHoliday(Request::create('/', 'PATCH', ['manager_reply' => 1, 'manager_reply_reason' => 'مستوفى']), $id);
        $this->assertDatabaseHas('holidays_inquiry', ['id' => $id, 'branch_id' => 5, 'companies_groups_id' => 1, 'manager_reply' => 1, 'manager' => 10]);
        $this->assertSame('application/pdf', $this->controller->holidayPdf($id)->headers->get('content-type'));
        session(['hr_branch_id' => 2]);
        $this->expectException(HttpException::class);
        $this->controller->holidayTimeline($id);
    }

    public function test_medical_report_approval_enforces_legacy_branch_allow_list_and_company_scope(): void
    {
        DB::table('medica_report')->insert(['id' => 1, 'branch_id' => 7, 'companies_groups_id' => 1, 'user_id' => 10, 'patient_name' => 'مريض', 'file_number' => '9', 'manager_reply' => 0, 'created_at' => '1']);
        session(['hr_branch_id' => 7]);
        $this->controller->decideMedicalReport(Request::create('/', 'PATCH', ['manager_reply' => 2, 'manager_reply_reason' => 'ناقص']), 1);
        $this->assertDatabaseHas('medica_report', ['id' => 1, 'manager_reply' => 2]);
        $this->assertSame('application/pdf', $this->controller->medicalReportPdf(1)->headers->get('content-type'));
        session(['hr_branch_id' => 2]);
        $this->expectException(HttpException::class);
        $this->controller->medicalReports(Request::create('/'));
    }

    public function test_memo_recipients_are_limited_to_same_branch_and_company(): void
    {
        DB::table('ra_users')->insert(['hr_id' => 20, 'branch_id' => 2, 'companies_groups_id' => 1, 'activated' => 1, 'isSearchedField' => 1, 'hr_first_name' => 'آخر', 'hr_last_name' => 'ب']);
        $valid = Request::create('/', 'POST', ['memo_types_id' => 1, 'title' => 'عنوان', 'memo' => 'نص', 'recipients' => [10]]);
        $this->controller->storeMemo($valid);
        $this->assertDatabaseHas('memo_send_to', ['user_id' => 10]);
        $memo = DB::table('memo')->first();
        $this->controller->publicMemo($memo->sms_tocken, $memo->id, 10);
        $this->assertNotNull(DB::table('memo_send_to')->where('memo_id', $memo->id)->value('seen_at'));
        $this->assertSame('application/pdf', $this->controller->memoPdf($memo->id)->headers->get('content-type'));
        $this->expectException(HttpException::class);
        $this->controller->storeMemo(Request::create('/', 'POST', ['memo_types_id' => 1, 'memo' => 'نص', 'recipients' => [20]]));
    }

    public function test_coverage_is_restricted_to_legacy_branches(): void
    {
        session(['hr_branch_id' => 4]);
        $this->expectException(HttpException::class);
        $this->controller->coverage(Request::create('/'));
    }

    public function test_coverage_create_and_pdf_are_scoped(): void
    {
        $request = Request::create('/', 'POST', ['memo_types_id' => 1, 'memo' => 'تفاصيل', 'patient_name' => 'مريض', 'file_number' => 'F1', 'id_number' => '123', 'coverage_authority' => 'شركة', 'amount_required' => '500', 'hospitalization_days' => '2', 'patient_mobile' => '0500000000']);
        $this->controller->storeCoverage($request);
        $id = (int) DB::table('service_coverage_memo')->value('id');
        $this->assertDatabaseHas('service_coverage_memo', ['id' => $id, 'branch_id' => 1, 'companies_groups_id' => 1]);
        $record = DB::table('service_coverage_memo')->where('id', $id)->first();
        $this->controller->publicCoverage($record->sms_tocken, $id);
        $this->assertNotNull(DB::table('service_coverage_memo_send_to')->where('memo_id', $id)->value('seen_at'));
        $this->assertSame('application/pdf', $this->controller->coveragePdf($id)->headers->get('content-type'));
    }

    public function test_signature_is_stored_privately_and_resolved_only_for_current_user(): void
    {
        Storage::fake('local');
        $png = 'data:image/png;base64,'.base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $this->controller->storeSignature(Request::create('/', 'POST', ['signature' => $png]));
        $stored = DB::table('signatuers')->where('idno', 10)->first();
        $this->assertNotNull($stored);
        Storage::assertExists('legacy-office/signatures/'.$stored->pic);
        session(['hr_user_id' => 20]);
        $this->expectException(HttpException::class);
        $this->controller->signatureImage();
    }

    private function schemas(): void
    {
        Schema::create('holidays_inquiry', function (Blueprint $t): void {
            $t->increments('id');
            $t->integer('branch_id');
            $t->integer('companies_groups_id');
            $t->integer('user_id');
            $t->string('patient_name');
            $t->string('nationality');
            $t->string('idno');
            $t->string('file_number');
            $t->integer('days');
            $t->string('issue_date');
            $t->string('issuer');
            $t->integer('type');
            $t->string('created_at')->nullable();
            $t->integer('manager_reply')->nullable();
            $t->string('manager_reply_reason')->nullable();
            $t->integer('manager')->nullable();
            $t->string('date')->nullable();
        });
        Schema::create('holidays_inquiry_attachments', function (Blueprint $t): void {
            $t->increments('id');
            $t->integer('holidays_inquiry_id');
            $t->string('file_name');
            $t->integer('created_by');
            $t->timestamp('created_at')->nullable();
        });
        foreach (['holidays_inquiry_decision', 'medica_report_decision'] as $name) {
            Schema::create($name, function (Blueprint $t): void {
                $t->increments('id');
                $t->string('name_ar');
                $t->integer('publish');
            });
        }
        Schema::create('medica_report', function (Blueprint $t): void {
            $t->increments('id');
            $t->integer('branch_id');
            $t->integer('companies_groups_id');
            $t->integer('user_id');
            $t->string('patient_name');
            $t->string('file_number');
            $t->string('doctor')->nullable();
            foreach (['birth_date', 'entry_date', 'exit_date', 'medical_diagnosis', 'treatment', 'recommendation'] as $column) {
                $t->text($column)->nullable();
            }
            $t->string('created_at')->nullable();
            $t->integer('manager_reply')->nullable();
            $t->string('manager_reply_reason')->nullable();
            $t->integer('manager')->nullable();
            $t->string('date')->nullable();
        });
        foreach ([['memo_types', true], ['service_coverage_memo_types', true]] as [$name]) {
            Schema::create($name, function (Blueprint $t): void {
                $t->increments('id');
                $t->string('name_ar');
                $t->integer('publish');
                $t->integer('ranking');
            });
        }
        Schema::create('memo', function (Blueprint $t): void {
            $t->increments('id');
            $t->integer('branch_id');
            $t->integer('companies_groups_id');
            $t->integer('user_id');
            $t->integer('memo_types_id');
            $t->text('memo');
            $t->string('title')->nullable();
            $t->string('date');
            $t->string('sms_tocken');
            $t->integer('activated_by')->nullable();
            $t->timestamp('activated_at')->nullable();
            foreach (['minutes', 'days', 'month_year', 'check_in', 'check_out', 'exit_date', 'exit_time', 'closed_inquiries', 'pending_inquiries', 'current_begin_time', 'current_end_time', 'new_begin_time', 'new_end_time', 'hours', 'begin_date', 'end_date'] as $c) {
                $t->string($c)->nullable();
            }
        });
        Schema::create('memo_send_to', function (Blueprint $t): void {
            $t->increments('id');
            $t->integer('memo_id');
            $t->integer('user_id');
            $t->timestamp('seen_at')->nullable();
        });
        Schema::create('service_coverage_memo', function (Blueprint $t): void {
            $t->increments('id');
            $t->integer('branch_id');
            $t->integer('companies_groups_id');
            $t->integer('user_id');
            $t->integer('memo_types_id');
            $t->text('memo');
            $t->string('date');
            $t->string('sms_tocken');
            $t->integer('activated_by')->nullable();
            $t->timestamp('activated_at')->nullable();
            foreach (['patient_name', 'file_number', 'id_number', 'coverage_authority', 'amount_required', 'hospitalization_days', 'patient_mobile'] as $c) {
                $t->string($c)->nullable();
            }
        });
        Schema::create('service_coverage_memo_send_to', function (Blueprint $t): void {
            $t->increments('id');
            $t->integer('memo_id');
            $t->timestamp('seen_at')->nullable();
        });
        Schema::create('ra_users', function (Blueprint $t): void {
            $t->integer('hr_id')->primary();
            $t->integer('branch_id');
            $t->integer('companies_groups_id');
            $t->integer('activated');
            $t->integer('isSearchedField');
            $t->string('hr_first_name');
            $t->string('hr_last_name')->nullable();
            $t->string('mobile')->nullable();
        });
        Schema::create('signatuers', function (Blueprint $t): void {
            $t->increments('id');
            $t->integer('idno');
            $t->string('pic');
            $t->integer('type');
        });
    }
}
