<?php

namespace Tests\Feature;

use App\Services\LegalClaims\LegalClaimService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegalClaimTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');
        Schema::create('lawsuit', function (Blueprint $b): void { $b->increments('id'); $b->integer('date'); $b->integer('branch_id'); $b->integer('companies_groups_id'); $b->integer('lawsuit_payment_type_id'); $b->string('patient_name'); $b->string('file_number'); foreach (['admission_date','discharge_date','patient_idno','amount_paid','amount_rest','liable_name','liable_idno','liable_nationality','liable_mobile','lawsuit_admission_location_id','covered_amount','uncovered_amount','received_date','lawsuit_request_status_id','lawsuit_rejected_reason_id','date_type','birth_day','birth_month','birth_year','rejected_reason','sexCode','contractor_approval_date','service_provided_to_patient'] as $column) $b->string($column)->nullable(); $b->integer('patient_nationality')->nullable(); $b->integer('status')->nullable(); $b->string('file_1')->nullable(); $b->string('file_2')->nullable(); $b->string('file_3')->nullable(); $b->string('file_4')->nullable(); $b->integer('created_by'); $b->integer('updated_by')->nullable(); $b->timestamp('updated_at')->nullable(); });
        Schema::create('lawsuit_status', function (Blueprint $b): void { $b->increments('id'); $b->string('name_ar'); $b->integer('publish')->default(1); $b->integer('ranking')->default(1); });
        Schema::create('lawsuit_payment_type', function (Blueprint $b): void { $b->increments('id'); $b->string('name_ar'); $b->integer('publish')->default(1); });
        Schema::create('lawsuit_actions', function (Blueprint $b): void { $b->increments('id'); $b->integer('lawsuit_id'); $b->integer('status_id'); $b->integer('branch_id'); $b->text('details')->nullable(); $b->integer('created_by'); $b->timestamp('created_at')->nullable(); $b->string('request_number')->nullable(); $b->string('request_date')->nullable(); $b->string('case_number')->nullable(); $b->string('sessions_number')->nullable(); $b->text('session_summary')->nullable(); $b->string('sessions_date')->nullable(); $b->string('next_sessions_date')->nullable(); $b->string('lawsuit_request_file')->nullable(); $b->string('session_1_file')->nullable(); });
        Schema::create('lawsuit_attachments', function (Blueprint $b): void { $b->increments('id'); $b->integer('lawsuit_id'); $b->string('file_name'); $b->integer('created_by'); $b->timestamp('created_at')->nullable(); });
        Schema::create('lawsuit_statement_request', function (Blueprint $b): void { $b->increments('id'); $b->integer('lawsuit_id'); $b->integer('branch_id'); $b->text('details'); $b->integer('created_by'); $b->timestamp('created_at')->nullable(); $b->string('file')->nullable(); });
        Schema::create('lawsuit_reconciliation_installment', function (Blueprint $b): void { $b->increments('id'); $b->integer('lawsuit_id'); $b->integer('branch_id'); $b->integer('companies_groups_id'); $b->string('installment_date'); $b->integer('created_by'); $b->integer('payment_status')->nullable(); $b->string('payment_date')->nullable(); $b->integer('payment_intered_by')->nullable(); });
        Schema::create('lawsuit_suspend_case_request', function (Blueprint $b): void { $b->increments('id'); $b->integer('lawsuit_id'); $b->integer('lawsuit_suspend_case_request_status_id'); $b->integer('branch_id'); $b->integer('created_by'); $b->string('total_amount')->nullable(); $b->string('amount_waived')->nullable(); $b->string('file')->nullable(); });
        Schema::create('lawsuit_suspend_case_request_status', function (Blueprint $b): void { $b->increments('id'); $b->string('name_ar'); $b->integer('publish')->default(1); $b->integer('ranking')->default(1); });
        foreach (['lawsuit_admission_location', 'lawsuit_request_status', 'lawsuit_rejected_reason'] as $table) Schema::create($table, function (Blueprint $b): void { $b->increments('id'); $b->string('name_ar'); $b->integer('publish')->default(1); });
        DB::table('lawsuit_payment_type')->insert(['id' => 1, 'name_ar' => 'دفع', 'publish' => 1]); DB::table('lawsuit_status')->insert(['id' => 1, 'name_ar' => 'جديد', 'publish' => 1, 'ranking' => 1]); DB::table('lawsuit_suspend_case_request_status')->insert(['id' => 1, 'name_ar' => 'طلب', 'publish' => 1, 'ranking' => 1]);
        session(['hr_user_id' => 10, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
    }

    public function test_claim_action_and_scope_are_enforced(): void
    {
        $service = app(LegalClaimService::class);
        $id = $service->create(['lawsuit_payment_type_id' => 1, 'patient_name' => 'مريض', 'file_number' => 'L-1', 'service_provided_to_patient' => 'خدمة'], ['file_1' => UploadedFile::fake()->create('claim.pdf')]);
        $this->assertSame('مريض', $service->find($id)->patient_name);
        $service->addAction($id, ['status_id' => 1, 'details' => 'تمت المراجعة'], []);
        $this->assertDatabaseHas('lawsuit_actions', ['lawsuit_id' => $id, 'details' => 'تمت المراجعة']);
        $service->addInstallment($id, '2026-08-15');
        $installment = DB::table('lawsuit_reconciliation_installment')->first();
        $service->markInstallmentPaid($id, $installment->id);
        $service->addSuspension($id, ['status_id' => 1, 'total_amount' => 100, 'amount_waived' => 10], null);
        $this->assertDatabaseHas('lawsuit_suspend_case_request', ['lawsuit_id' => $id, 'amount_waived' => 10]);
        session(['companies_groups_id' => 2]);
        $this->assertNull($service->find($id));
    }
}
