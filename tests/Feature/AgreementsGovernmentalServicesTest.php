<?php

namespace Tests\Feature;

use App\Services\LegacyWorkflows\GovernmentalServiceWorkflow;
use App\Services\LegacyWorkflows\MedicalAgreementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AgreementsGovernmentalServicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');
        Storage::fake('public');
        Schema::create('ra_users', fn (Blueprint $b) => $this->userColumns($b));
        foreach (['country_yakeen', 'IDType', 'relatives'] as $table) {
            Schema::create($table, function (Blueprint $b) use ($table): void {
                $b->increments('id');
                if ($table === 'country_yakeen') {
                    $b->integer('CODE');
                    $b->string('DESCRIPTION');
                } else {
                    $b->string('name_ar');
                }
            });
        }
        Schema::create('payment_guarantee', fn (Blueprint $b) => $this->agreementColumns($b, false));
        Schema::create('medical_services_agreement_sadq', fn (Blueprint $b) => $this->agreementColumns($b, true));
        Schema::create('payment_guarantee_attachments', function (Blueprint $b): void {
            $b->increments('id');
            $b->integer('payment_guarantee_id');
            $b->string('file_name');
            $b->integer('created_by');
        });
        Schema::create('medical_services_agreement_sadq_transactions', function (Blueprint $b): void {
            $b->increments('id');
            $b->string('reference_number');
            $b->string('signStatus')->nullable();
        });
        Schema::create('roles', function (Blueprint $b): void {
            $b->increments('role_id');
            $b->string('role_name');
        });
        Schema::create('permissions', function (Blueprint $b): void {
            $b->increments('perm_id');
            $b->string('perm_desc');
        });
        Schema::create('user_role', function (Blueprint $b): void {
            $b->integer('user_id');
            $b->integer('role_id');
        });
        Schema::create('role_perm', function (Blueprint $b): void {
            $b->integer('role_id');
            $b->integer('perm_id');
        });
        Schema::create('governmental_services', fn (Blueprint $b) => $this->governmentColumns($b));
        Schema::create('governmental_services_type', function (Blueprint $b): void {
            $b->increments('id');
            $b->integer('platform_id')->default(1);
            $b->string('name_ar');
            $b->integer('publish')->default(1);
        });
        Schema::create('governmental_services_platforms', function (Blueprint $b): void {
            $b->increments('id');
            $b->string('name_ar');
            $b->integer('publish')->default(1);
        });
        Schema::create('governmental_services_action_type', function (Blueprint $b): void {
            $b->increments('id');
            $b->string('name_ar');
            $b->integer('publish')->default(1);
        });
        Schema::create('governmental_services_action', function (Blueprint $b): void {
            $b->increments('id');
            $b->integer('governmental_services_id');
            $b->integer('status_id');
            $b->integer('branch_id');
            $b->string('details')->nullable();
            $b->integer('created_by');
            $b->timestamp('created_at')->nullable();
        });
        Schema::create('governmental_services_attachments', function (Blueprint $b): void {
            $b->increments('id');
            $b->integer('governmental_services_id');
            $b->string('file_name');
            $b->integer('created_by');
            $b->timestamp('created_at')->nullable();
        });
        DB::table('ra_users')->insert(['hr_id' => 10, 'hr_first_name' => 'مدقق', 'hr_last_name' => 'أ', 'branch_id' => 1, 'companies_groups_id' => 1]);
        DB::table('country_yakeen')->insert(['CODE' => 1, 'DESCRIPTION' => 'سعودي']);
        DB::table('IDType')->insert(['name_ar' => 'هوية']);
        DB::table('relatives')->insert(['name_ar' => 'قريب']);
        DB::table('governmental_services_type')->insert([['id' => 1, 'name_ar' => 'رخصة'], ['id' => 3, 'name_ar' => 'فحص زواج']]);
        DB::table('governmental_services_platforms')->insert(['id' => 1, 'name_ar' => 'منصة صحة']);
        DB::table('governmental_services_action_type')->insert([['id' => 1, 'name_ar' => 'تم الإدخال'], ['id' => 3, 'name_ar' => 'مرفوض']]);
    }

    public function test_standard_agreement_preserves_company_and_branch_scope_and_attachment_ownership(): void
    {
        session(['hr_user_id' => 10, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
        $service = app(MedicalAgreementService::class);
        $id = $service->create('standard', $this->agreementData());
        $this->assertSame('1234567890', $service->find('standard', $id)->patient_idno);
        $service->attach('standard', $id, UploadedFile::fake()->create('proof.pdf'));
        $attachment = DB::table('payment_guarantee_attachments')->first();
        $this->assertNotNull($service->attachment('standard', $id, $attachment->id));
        session(['hr_branch_id' => 2]);
        $this->assertNull($service->find('standard', $id));
    }

    public function test_sadq_page_enforces_the_legacy_privilege(): void
    {
        session(['hr_user_id' => 10, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
        $this->expectException(HttpException::class);
        app(MedicalAgreementService::class)->list('sadq', ['from' => '', 'to' => '', 'language' => '', 'creator' => '', 'id_number' => '', 'status' => '']);
    }

    public function test_sadq_privileged_user_can_create_scoped_agreement(): void
    {
        session(['hr_user_id' => 10, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
        DB::table('roles')->insert(['role_id' => 1, 'role_name' => 'agreements']);
        DB::table('permissions')->insert(['perm_id' => 7, 'perm_desc' => 'Medical Services Provision Agreement Yaqeen']);
        DB::table('user_role')->insert(['user_id' => 10, 'role_id' => 1]);
        DB::table('role_perm')->insert(['role_id' => 1, 'perm_id' => 7]);
        $id = app(MedicalAgreementService::class)->create('sadq', $this->agreementData());
        $this->assertDatabaseHas('medical_services_agreement_sadq', ['id' => $id, 'branch_id' => 1, 'companies_groups_id' => 1]);
    }

    public function test_government_processor_requires_attachment_before_completion_and_cannot_cross_company(): void
    {
        session(['hr_user_id' => 10, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
        $service = app(GovernmentalServiceWorkflow::class);
        $id = $service->create($this->governmentData());
        session(['hr_branch_id' => 7]);
        try {
            $service->transition($id, 1, null);
            $this->fail('Completion without attachment must fail.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $service->attach($id, UploadedFile::fake()->create('result.pdf'));
        $service->transition($id, 1, 'تم الإدخال');
        $this->assertDatabaseHas('governmental_services', ['id' => $id, 'status' => 1]);
        session(['companies_groups_id' => 2]);
        $this->expectException(HttpException::class);
        $service->find($id);
    }

    private function agreementData(): array
    {
        return ['language' => 1, 'contractor_type' => 2, 'patient_name_ar' => 'مريض', 'patient_name_en' => '', 'patient_idno' => '1234567890', 'patient_file_number' => 'MR-1', 'patient_nationality' => 1, 'contractor_name_ar' => '', 'contractor_name_en' => '', 'contractor_idno' => '', 'contractor_mobile' => '0500000000', 'contractor_nationality' => null, 'relative' => 0, 'date_type' => 2, 'birth_day' => 1, 'birth_month' => 1, 'birth_year' => 1990, 'patient_id_type' => 1, 'contractor_id_type' => 1, 'sex_code' => 1, 'email' => null];
    }

    private function governmentData(): array
    {
        return ['platform_id' => 1, 'service_id' => 1, 'patient' => 'مراجع', 'file_number' => 'F-1', 'id_no' => '1234567890', 'mobile' => '0500000000'];
    }

    private function userColumns(Blueprint $b): void
    {
        $b->integer('hr_id')->primary();
        $b->string('hr_first_name');
        $b->string('hr_last_name')->nullable();
        $b->integer('branch_id');
        $b->integer('companies_groups_id');
    }

    private function agreementColumns(Blueprint $b, bool $sadq): void
    {
        $b->increments('id');
        $b->integer('branch_id');
        $b->integer('companies_groups_id');
        $b->integer('user_id');
        foreach (['patient_name_ar', 'patient_name_en', 'patient_idno', 'patient_file_number', 'contractor_name_ar', 'contractor_name_en', 'contractor_idno', 'contractor_mobile', 'relative', 'created_at', 'email'] as $c) {
            $b->string($c)->nullable();
        } foreach (['patient_nationality', 'contractor_nationality', 'status', 'type', 'payment_type', 'language', 'contractor_type', 'date_type', 'birth_day', 'birth_month', 'birth_year', 'pateintIDType', 'contractorIDType', 'sexCode'] as $c) {
            $b->integer($c)->default(0);
        } $b->string('deserved_amount')->default('0');
        if ($sadq) {
            $b->string('reference_number')->unique();
        } else {
            $b->string('sms_tocken');
            $b->text('emdha_output')->nullable();
        }
    }

    private function governmentColumns(Blueprint $b): void
    {
        $b->increments('id');
        foreach (['date', 'pateint', 'file_number', 'id_no', 'mobile', 'job_title', 'address', 'sponser_id', 'pateint_wife', 'id_no_wife', 'mobile_wife', 'passport_no'] as $c) {
            $b->string($c)->nullable();
        } foreach (['branch_id', 'platform_id', 'service_id', 'date_type', 'birth_day', 'birth_month', 'birth_year', 'created_by', 'companies_groups_id', 'status', 'nationality_type', 'country', 'country_wife', 'date_type_wife', 'birth_day_wife', 'birth_month_wife', 'birth_year_wife', 'married_request_type', 'filledByClient'] as $c) {
            $b->integer($c)->default(0);
        } $b->timestamp('created_at')->nullable();
    }
}
