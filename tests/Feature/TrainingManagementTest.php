<?php

namespace Tests\Feature;

use App\Support\Training\TrainingPermissions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrainingManagementTest extends TestCase
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
        Schema::create('user_role', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('role_id');
        });
        Schema::create('training_confirmation_status', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name_ar');
            $table->string('type');
            $table->integer('publish')->default(1);
        });
        Schema::create('training_confirmation', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('branch_id');
            $table->integer('companies_groups_id');
            $table->integer('user_id');
            $table->integer('employee_id');
            $table->integer('training_coordinator');
            $table->string('training_hour');
            $table->dateTime('begin_date');
            $table->integer('days');
            $table->string('time_from');
            $table->string('time_to');
            $table->string('created_at')->nullable();
            $table->integer('status')->default(1);
            $table->integer('type')->default(1);
            $table->string('sms_tocken');
            $table->integer('publish')->default(1);
            $table->text('emdha_output')->nullable();
            $table->string('ClientOTPSessionID')->nullable();
            $table->text('signerInfo')->nullable();
        });
        Schema::create('training_confirmation_actions', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('training_confirmation_id');
            $table->integer('status_id')->nullable();
            $table->integer('branch_id')->nullable();
            $table->string('details', 200)->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at')->nullable();
        });

        DB::table('branches')->insert([['id' => 1, 'name_ar' => 'فرع أ'], ['id' => 2, 'name_ar' => 'فرع ب']]);
        DB::table('job_titles')->insert(['id' => 1, 'name_ar' => 'ممرض']);
        DB::table('ra_users')->insert([
            ['hr_id' => 1, 'hr_first_name' => 'PW_AUDIT_MANAGER', 'hr_username' => 'PW_AUDIT_MANAGER', 'branch_id' => 1, 'companies_groups_id' => 1, 'job_title' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['hr_id' => 2, 'hr_first_name' => 'PW_AUDIT_TRAINEE', 'hr_username' => 'PW_AUDIT_TRAINEE', 'branch_id' => 1, 'companies_groups_id' => 1, 'job_title' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['hr_id' => 3, 'hr_first_name' => 'PW_AUDIT_OTHER', 'hr_username' => 'PW_AUDIT_OTHER', 'branch_id' => 2, 'companies_groups_id' => 1, 'job_title' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('user_permission')->insert(['userid' => 1, 'page' => TrainingPermissions::MANAGEMENT, 'permit' => '2']);
        DB::table('user_role')->insert(['user_id' => 1, 'role_id' => 1]);
        DB::table('training_confirmation_status')->insert([
            ['id' => 1, 'name_ar' => 'تم إحالته لمنسق التدريب', 'type' => '3'], ['id' => 2, 'name_ar' => 'تحت التدريب', 'type' => '1'],
            ['id' => 3, 'name_ar' => 'تم اجتياز التدريب', 'type' => '1'], ['id' => 4, 'name_ar' => 'لم يتم اجتياز التدريب', 'type' => '1'],
            ['id' => 5, 'name_ar' => 'تمت مصادقة المتدرب', 'type' => '3'], ['id' => 6, 'name_ar' => 'اختبار مجتاز', 'type' => '2'],
            ['id' => 7, 'name_ar' => 'اختبار غير مجتاز', 'type' => '2'], ['id' => 8, 'name_ar' => 'إعادة للتدريب', 'type' => '2'],
        ]);
        DB::table('training_confirmation')->insert([
            $this->trainingRow(10, 1, 1, 2), $this->trainingRow(20, 2, 1, 3), $this->trainingRow(30, 1, 2, 2),
        ]);
        DB::table('training_confirmation_actions')->insert(['training_confirmation_id' => 10, 'status_id' => 1, 'branch_id' => 1, 'created_by' => 1, 'created_at' => now()]);
    }

    public function test_list_detail_and_documents_are_permission_and_scope_protected(): void
    {
        $session = $this->sessionFor(1);
        $this->withSession($session)->get(route('modules.training.management.index'))->assertOk()->assertSee('PW_AUDIT_TRAINEE')->assertDontSee('PW_AUDIT_OTHER');
        $this->withSession($session)->get(route('modules.training.management.show', 20))->assertNotFound();
        $this->withSession($session)->get(route('modules.training.management.show', 30))->assertNotFound();
        $this->withSession($session)->get(route('modules.training.management.document', [10, 'plan']))->assertOk()->assertHeader('content-type', 'application/pdf');

        DB::table('user_permission')->where('userid', 1)->delete();
        $this->withSession([...$session, 'hm_permissions' => []])->get(route('modules.training.management.index'))->assertForbidden();
    }

    public function test_management_creates_legacy_plan_and_only_allows_type_two_statuses(): void
    {
        $session = $this->sessionFor(1);
        $this->withSession($session)->post(route('modules.training.management.store'), [
            'employee_id' => 2, 'training_coordinator' => 1, 'begin_date' => '2026-08-03', 'time_from' => '08:00', 'time_to' => '16:00',
        ])->assertRedirect();

        $created = DB::table('training_confirmation')->where('employee_id', 2)->latest('id')->first();
        $this->assertSame('8', $created->training_hour);
        $this->assertSame(6, $created->days);
        $this->assertSame(1, $created->status);
        $this->assertDatabaseHas('training_confirmation_actions', ['training_confirmation_id' => $created->id, 'status_id' => 1, 'created_by' => 1]);

        $this->withSession($session)->post(route('modules.training.management.status', $created->id), ['status_id' => 3])->assertSessionHasErrors('status_id');
        $this->withSession($session)->post(route('modules.training.management.status', $created->id), ['status_id' => 7, 'details' => 'PW_AUDIT_REASON'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('training_confirmation', ['id' => $created->id, 'status' => 7]);
        $this->assertDatabaseHas('training_confirmation_actions', ['training_confirmation_id' => $created->id, 'status_id' => 7, 'details' => 'PW_AUDIT_REASON']);
    }

    private function sessionFor(int $id): array
    {
        return ['hr_user_id' => $id, 'hr_user_level' => 1, 'hr_branch_id' => 1, 'companies_groups_id' => 1, 'groupid' => 1];
    }

    private function trainingRow(int $id, int $branch, int $company, int $employee): array
    {
        return ['id' => $id, 'branch_id' => $branch, 'companies_groups_id' => $company, 'user_id' => 1, 'employee_id' => $employee,
            'training_coordinator' => 1, 'training_hour' => '8', 'begin_date' => '2026-08-01 00:00:00', 'days' => 6,
            'time_from' => '08:00', 'time_to' => '16:00', 'created_at' => '2026-08-01 08:00:00', 'status' => 1,
            'sms_tocken' => md5((string) $id), 'publish' => 1];
    }
}
