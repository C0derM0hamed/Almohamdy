<?php

namespace Tests\Feature;

use App\Services\MedicalAppointment\MedicalAppointmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MedicalAppointmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('ra_users', function (Blueprint $table): void {
            $table->integer('hr_id')->primary();
            $table->string('hr_first_name')->nullable();
            $table->string('hr_last_name')->nullable();
            $table->string('hr_email_address')->default('');
            $table->string('hr_username');
            $table->string('hr_password')->default('');
            $table->integer('hr_user_level')->default(1);
            $table->integer('branch_id');
            $table->integer('companies_groups_id');
            $table->integer('groupid')->default(1);
            $table->string('mobile')->nullable();
            $table->integer('job_title')->nullable();
            $table->string('hr_last_login')->nullable();
            $table->string('lastPassChange')->nullable();
            $table->integer('department_supervisor')->default(0);
            $table->integer('activated')->default(1);
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('last_failed_login')->nullable();
            $table->timestamps();
        });
        Schema::create('user_permission', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('userid');
            $table->integer('pageid')->default(0);
            $table->string('page');
            $table->string('permit');
        });
        Schema::create('user_groups_permission', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('groupid');
            $table->string('page');
            $table->string('permit');
        });
        Schema::create('job_titles', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
        });
        Schema::create('branches', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
        });
        Schema::create('book_a_medical_appointment_status', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->integer('publish')->default(1);
        });
        Schema::create('specialized_clinics', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('subject_ar');
            $table->string('subject_en')->nullable();
            $table->integer('publish')->default(1);
        });
        Schema::create('clinicians', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('specialized_clinics_id')->nullable();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('mobile')->nullable();
            $table->integer('publish')->default(1);
        });
        Schema::create('book_a_medical_appointment_procedure_place', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->integer('publish')->default(1);
        });
        Schema::create('book_a_medical_appointment_coverage_status', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->integer('publish')->default(1);
        });
        Schema::create('book_a_medical_appointment_not_wanting', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name_ar');
            $table->string('name_en')->nullable();
        });
        Schema::create('book_a_medical_appointment', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('date');
            $table->integer('branch_id');
            $table->string('mobile');
            $table->integer('department');
            $table->string('file_number');
            $table->string('patient_name');
            $table->integer('physician');
            $table->string('procedure_type');
            $table->integer('procedure_place');
            $table->string('procedure_duration');
            $table->integer('medical_coverage_status');
            $table->integer('created_by');
            $table->integer('companies_groups_id');
            $table->string('token');
            $table->integer('status')->default(1);
            $table->integer('language');
            $table->integer('language_doctor');
            $table->string('patient_name_en')->nullable();
            $table->string('procedure_type_en')->nullable();
            $table->string('procedure_duration_en')->nullable();
            $table->integer('patient_confirm_date')->nullable();
            $table->integer('patient_confirm_date_timestamp')->nullable();
            $table->integer('patient_confirm_date_notice')->nullable();
            $table->integer('doctor_action')->nullable();
            $table->integer('doctor_action_timestmap')->nullable();
            $table->string('cleint_cancel_reason')->nullable();
        });
        Schema::create('book_a_medical_appointment_time', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('book_a_medical_appointment_id');
            $table->string('date');
        });
        Schema::create('book_a_medical_appointment_timeline', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('book_a_medical_appointment_id');
            $table->integer('status_id');
            $table->integer('date');
            $table->string('notice')->nullable();
            $table->integer('created_by');
        });

        DB::table('branches')->insert([
            ['id' => 1, 'name_ar' => 'فرع أ'], ['id' => 2, 'name_ar' => 'فرع ب'],
        ]);
        DB::table('job_titles')->insert(['id' => 1, 'name_ar' => 'موظف']);
        DB::table('ra_users')->insert([
            ['hr_id' => 1, 'hr_first_name' => 'PW_AUDIT_MEDICAL', 'hr_username' => 'PW_AUDIT_MEDICAL', 'hr_user_level' => 1, 'branch_id' => 2, 'companies_groups_id' => 1, 'job_title' => 1, 'activated' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['hr_id' => 2, 'hr_first_name' => 'PW_AUDIT_OTHER', 'hr_username' => 'PW_AUDIT_OTHER', 'hr_user_level' => 1, 'branch_id' => 1, 'companies_groups_id' => 1, 'job_title' => 1, 'activated' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['hr_id' => 3, 'hr_first_name' => 'PW_AUDIT_SUPER', 'hr_username' => 'PW_AUDIT_SUPER', 'hr_user_level' => 3, 'branch_id' => 1, 'companies_groups_id' => 1, 'job_title' => 1, 'activated' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('specialized_clinics')->insert(['id' => 1, 'subject_ar' => 'عيادة القلب', 'subject_en' => 'Cardiology']);
        DB::table('clinicians')->insert(['id' => 1, 'specialized_clinics_id' => 1, 'name_ar' => 'د. أحمد', 'name_en' => 'Dr. Ahmed', 'mobile' => '0550000000', 'publish' => 1]);
        DB::table('book_a_medical_appointment_procedure_place')->insert(['id' => 1, 'name_ar' => 'غرفة العمليات', 'name_en' => 'Operating Room']);
        DB::table('book_a_medical_appointment_coverage_status')->insert(['id' => 1, 'name_ar' => 'مغطى', 'name_en' => 'Covered']);
        DB::table('book_a_medical_appointment_not_wanting')->insert([
            ['id' => 4, 'name_ar' => 'أريد تأجيل الموعد', 'name_en' => 'Postpone'],
            ['id' => 5, 'name_ar' => 'أرفض تحديد الموعد', 'name_en' => 'Decline'],
            ['id' => 6, 'name_ar' => 'سبب آخر', 'name_en' => 'Other'],
        ]);
        DB::table('book_a_medical_appointment_status')->insert([
            ['id' => 1, 'name_ar' => 'تم ارسال طلب تحديد الموعد للمراجع', 'publish' => 1],
            ['id' => 2, 'name_ar' => 'تم الأختيار من قبل المراجع بإنتظار تأكيد الطبيب', 'publish' => 1],
            ['id' => 3, 'name_ar' => 'تم التأكيد من قبل الطبيب', 'publish' => 1],
            ['id' => 4, 'name_ar' => 'تم طلب إعادة جدولة من قبل المراجع', 'publish' => 1],
            ['id' => 5, 'name_ar' => 'إرسال موعد جديد للمراجع', 'publish' => 1],
            ['id' => 6, 'name_ar' => 'معلق بناء على رغبة المراجع', 'publish' => 1],
            ['id' => 7, 'name_ar' => 'ملغي', 'publish' => 1],
            ['id' => 8, 'name_ar' => 'تم عمل الإجراء للمراجع', 'publish' => 1],
            ['id' => 9, 'name_ar' => 'الطبيب لم يحضر', 'publish' => 1],
            ['id' => 10, 'name_ar' => 'المراجع لم يحضر', 'publish' => 1],
            ['id' => 11, 'name_ar' => 'تم طلب إعادة جدولة من الطبيب', 'publish' => 1],
            ['id' => 12, 'name_ar' => 'المريض غير مؤهل للعملية الجراحية', 'publish' => 1],
            ['id' => 13, 'name_ar' => 'تم التأكيد من قبل قسم العمليات', 'publish' => 0],
            ['id' => 14, 'name_ar' => 'تم التأكيد من قبل قسم التنويم', 'publish' => 0],
        ]);
        DB::table('book_a_medical_appointment')->insert([
            ['id' => 90, 'date' => time(), 'branch_id' => 1, 'mobile' => '0500000000', 'department' => 1, 'file_number' => 'PW_BRANCH1', 'patient_name' => 'فرع غير مسموح', 'physician' => 1, 'procedure_type' => 'إجراء', 'procedure_place' => 1, 'procedure_duration' => '30 دقيقة', 'medical_coverage_status' => 1, 'created_by' => 2, 'companies_groups_id' => 1, 'token' => 'PW_BRANCH1_TOKEN', 'status' => 1, 'language' => 1, 'language_doctor' => 2],
            ['id' => 91, 'date' => time(), 'branch_id' => 2, 'mobile' => '0500000001', 'department' => 1, 'file_number' => 'PW_PUBLIC_REJECT', 'patient_name' => 'مراجع رفض', 'physician' => 1, 'procedure_type' => 'إجراء', 'procedure_place' => 1, 'procedure_duration' => '30 دقيقة', 'medical_coverage_status' => 1, 'created_by' => 1, 'companies_groups_id' => 1, 'token' => 'PW_PUBLIC_REJECT_TOKEN', 'status' => 1, 'language' => 1, 'language_doctor' => 2],
            ['id' => 92, 'date' => time(), 'branch_id' => 2, 'mobile' => '0500000002', 'department' => 1, 'file_number' => 'PW_PUBLIC_DOCTOR', 'patient_name' => 'مراجع موافق', 'physician' => 1, 'procedure_type' => 'إجراء', 'procedure_place' => 1, 'procedure_duration' => '30 دقيقة', 'medical_coverage_status' => 1, 'created_by' => 1, 'companies_groups_id' => 1, 'token' => 'PW_PUBLIC_DOCTOR_TOKEN', 'status' => 1, 'language' => 1, 'language_doctor' => 2],
            ['id' => 93, 'date' => time(), 'branch_id' => 2, 'mobile' => '0500000003', 'department' => 1, 'file_number' => 'PW_PUBLIC_RESCHEDULE', 'patient_name' => 'مراجع إعادة جدولة', 'physician' => 1, 'procedure_type' => 'إجراء', 'procedure_place' => 1, 'procedure_duration' => '30 دقيقة', 'medical_coverage_status' => 1, 'created_by' => 1, 'companies_groups_id' => 1, 'token' => 'PW_PUBLIC_RESCHEDULE_TOKEN', 'status' => 1, 'language' => 1, 'language_doctor' => 2],
        ]);
        DB::table('book_a_medical_appointment_time')->insert([
            ['book_a_medical_appointment_id' => 90, 'date' => '2026-08-03T09:00'],
            ['book_a_medical_appointment_id' => 91, 'date' => '2026-08-04T09:00'],
            ['book_a_medical_appointment_id' => 92, 'date' => '2026-08-05T09:00'],
            ['book_a_medical_appointment_id' => 93, 'date' => '2026-08-06T09:00'],
        ]);
        DB::table('book_a_medical_appointment_timeline')->insert([
            ['book_a_medical_appointment_id' => 90, 'status_id' => 1, 'date' => time(), 'notice' => '', 'created_by' => 2],
            ['book_a_medical_appointment_id' => 91, 'status_id' => 1, 'date' => time(), 'notice' => '', 'created_by' => 1],
            ['book_a_medical_appointment_id' => 92, 'status_id' => 1, 'date' => time(), 'notice' => '', 'created_by' => 1],
            ['book_a_medical_appointment_id' => 93, 'status_id' => 1, 'date' => time(), 'notice' => '', 'created_by' => 1],
        ]);
    }

    public function test_manager_pages_are_scoped_and_support_create_status_and_pdfs(): void
    {
        $session = $this->sessionFor(1, 2);
        $this->withSession($session)->get(route('modules.medical-appointments.index'))
            ->assertOk()
            ->assertSee('PW_PUBLIC_REJECT')
            ->assertDontSee('PW_BRANCH1');
        $this->withSession($session)->get(route('modules.medical-appointments.show', 90))->assertNotFound();

        $this->withSession($session)->post(route('modules.medical-appointments.store'), [
            'language' => 1,
            'language_doctor' => 2,
            'mobile' => '0501234567',
            'file_number' => 'PW_AUDIT_NEW',
            'department' => 1,
            'physician' => 1,
            'procedure_place' => 1,
            'medical_coverage_status' => 1,
            'patient_name' => 'PW_AUDIT_PATIENT',
            'procedure_type' => 'PW_AUDIT_TYPE',
            'procedure_duration' => '60 دقيقة',
            'date' => ['2026-08-06T08:30', '2026-08-07T08:30'],
        ])->assertRedirect();

        $created = DB::table('book_a_medical_appointment')->where('file_number', 'PW_AUDIT_NEW')->first();
        $this->assertNotNull($created);
        $this->assertSame(1, $created->status);
        $this->assertDatabaseHas('book_a_medical_appointment_time', ['book_a_medical_appointment_id' => $created->id, 'date' => '2026-08-06T08:30']);
        $this->assertDatabaseHas('book_a_medical_appointment_timeline', ['book_a_medical_appointment_id' => $created->id, 'status_id' => 1]);

        $this->withSession($session)->get(route('modules.medical-appointments.document', [$created->id, 'request']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->withSession($session)->get(route('modules.medical-appointments.timeline', $created->id))->assertOk();

        $this->withSession($session)->post(route('modules.medical-appointments.status', $created->id), [
            'status_id' => 5,
            'date' => '2026-08-08T10:15',
        ])->assertRedirect();
        $this->assertDatabaseHas('book_a_medical_appointment', ['id' => $created->id, 'status' => 5]);
        $this->assertDatabaseHas('book_a_medical_appointment_time', ['book_a_medical_appointment_id' => $created->id, 'date' => '2026-08-08T10:15']);

        $this->withSession($this->sessionFor(2, 1))
            ->get(route('modules.medical-appointments.index'))
            ->assertForbidden();

        $superAdmin = $this->sessionFor(3, 1);
        $this->assertDatabaseHas('book_a_medical_appointment', ['id' => 90, 'branch_id' => 1]);
        $this->startSession();
        session()->put($superAdmin);
        $this->assertNotNull(app(MedicalAppointmentService::class)->find(90));
        // المدير العام لا يُحصر في سجلات الفرع الموجود في الجلسة.
        $this->withSession($superAdmin)->get(route('modules.medical-appointments.show', 90))->assertOk();
        $this->withSession($superAdmin)->get(route('modules.medical-appointments.index'))
            ->assertOk();
    }

    public function test_public_patient_accepts_slot_and_doctor_confirms(): void
    {
        $slot = '2026-08-05T09:00';
        $slotTimestamp = strtotime($slot);

        $this->get(route('public.medical-appointments.patient.show', 'PW_PUBLIC_DOCTOR_TOKEN'))->assertOk();
        $this->post(route('public.medical-appointments.patient.store', 'PW_PUBLIC_DOCTOR_TOKEN'), [
            'patient_confirm_date' => $slotTimestamp,
        ])->assertRedirect(route('public.medical-appointments.patient.result'));

        $record = DB::table('book_a_medical_appointment')->where('token', 'PW_PUBLIC_DOCTOR_TOKEN')->first();
        $this->assertSame(2, $record->status);
        $this->assertSame($slotTimestamp, $record->patient_confirm_date);

        $this->get(route('public.medical-appointments.doctor.show', 'PW_PUBLIC_DOCTOR_TOKEN'))->assertOk();
        $this->post(route('public.medical-appointments.doctor.store', 'PW_PUBLIC_DOCTOR_TOKEN'), [
            'doctor_action' => 1,
        ])->assertRedirect(route('public.medical-appointments.doctor.result'));

        $record = DB::table('book_a_medical_appointment')->where('token', 'PW_PUBLIC_DOCTOR_TOKEN')->first();
        $this->assertSame(3, $record->status);
        $this->assertSame(1, $record->doctor_action);
    }

    public function test_public_patient_rejects_on_legacy_reason_branch(): void
    {
        $this->post(route('public.medical-appointments.patient.store', 'PW_PUBLIC_REJECT_TOKEN'), [
            'patient_confirm_date' => 5,
        ])->assertRedirect(route('public.medical-appointments.patient.result'));

        $record = DB::table('book_a_medical_appointment')->where('token', 'PW_PUBLIC_REJECT_TOKEN')->first();
        $this->assertSame(7, $record->status);
        $this->assertSame(5, $record->patient_confirm_date_notice);
    }

    public function test_public_patient_accepts_and_doctor_reschedules_another_record(): void
    {
        $slot = '2026-08-06T09:00';
        $slotTimestamp = strtotime($slot);

        $this->post(route('public.medical-appointments.patient.store', 'PW_PUBLIC_RESCHEDULE_TOKEN'), [
            'patient_confirm_date' => $slotTimestamp,
        ])->assertRedirect(route('public.medical-appointments.patient.result'));

        $this->post(route('public.medical-appointments.doctor.store', 'PW_PUBLIC_RESCHEDULE_TOKEN'), [
            'doctor_action' => 2,
        ])->assertRedirect(route('public.medical-appointments.doctor.result'));

        $record = DB::table('book_a_medical_appointment')->where('token', 'PW_PUBLIC_RESCHEDULE_TOKEN')->first();
        $this->assertSame(11, $record->status);
        $this->assertSame(2, $record->doctor_action);
    }

    private function sessionFor(int $userId, int $branchId): array
    {
        return [
            'hr_user_id' => $userId,
            'hr_user_level' => 1,
            'hr_branch_id' => $branchId,
            'companies_groups_id' => 1,
            'groupid' => 1,
        ];
    }
}
