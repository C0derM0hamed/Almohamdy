<?php

namespace Tests\Feature;

use App\Models\EmployeeVacation;
use App\Services\EmployeeLeave\LeaveRequestService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeLeaveLegacyScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['hm.employee_leave.approval_status_ids' => [
            'approved' => 1,
            'rejected' => 2,
            'partial' => 3,
            'pending' => 4,
        ]]);

        foreach (['client_vacations_hr_reply', 'client_vacations_branch_reply', 'emp_vacations', 'branch_users', 'approval_status'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('emp_vacations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('branch_id');
            $table->integer('companies_groups_id');
            $table->integer('emp_id');
            $table->integer('vac_type')->default(1);
            $table->integer('days')->default(1);
            $table->string('date')->default('0');
            $table->string('started_date')->default('0');
            $table->string('direct_date')->default('0');
            $table->integer('branch_approval')->default(0);
            $table->integer('hr_approval')->nullable();
            $table->integer('publish')->default(0);
            $table->string('nationality')->nullable();
            $table->string('job_title')->nullable();
            $table->string('contacts')->nullable();
            $table->string('email')->nullable();
            $table->string('other_contacts')->nullable();
        });
        Schema::create('client_vacations_branch_reply', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('vac_id');
            $table->integer('status_id');
            $table->string('date')->default('0');
            $table->string('comment')->nullable();
            $table->integer('data_entry_id')->nullable();
        });
        Schema::create('client_vacations_hr_reply', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('vac_id');
            $table->integer('status_id');
            $table->string('date')->default('0');
            $table->string('comment')->nullable();
            $table->integer('data_entry_id')->nullable();
        });
        Schema::create('branch_users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('br_user_full_name')->nullable();
            $table->string('br_user_mobile')->nullable();
            $table->string('br_user_username')->nullable();
        });
        Schema::create('approval_status', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();
            $table->integer('publish')->default(1);
        });

        DB::table('branch_users')->insert([
            ['id' => 10, 'br_user_full_name' => 'Owner'],
            ['id' => 11, 'br_user_full_name' => 'Peer'],
        ]);
        DB::table('approval_status')->insert([
            ['id' => 1, 'name_en' => 'Approved', 'name_ar' => 'مقبول'],
            ['id' => 2, 'name_en' => 'Rejected', 'name_ar' => 'مرفوض'],
            ['id' => 4, 'name_en' => 'Pending', 'name_ar' => 'جديد'],
        ]);
        DB::table('emp_vacations')->insert([
            ['id' => 1, 'branch_id' => 10, 'companies_groups_id' => 1, 'emp_id' => 10],
            ['id' => 2, 'branch_id' => 20, 'companies_groups_id' => 1, 'emp_id' => 11],
            ['id' => 3, 'branch_id' => 10, 'companies_groups_id' => 1, 'emp_id' => 11],
        ]);
        DB::table('client_vacations_branch_reply')->insert([
            ['vac_id' => 1, 'status_id' => 4],
            ['vac_id' => 2, 'status_id' => 4],
            ['vac_id' => 3, 'status_id' => 4],
        ]);
    }

    public function test_branch_user_cannot_see_other_branch_or_process_own_leave(): void
    {
        session([
            'hr_user_id' => 10,
            'hr_user_level' => 1,
            'hr_branch_id' => 10,
            'companies_groups_id' => 1,
        ]);

        $service = app(LeaveRequestService::class);

        $this->assertNull($service->findForDetail(2));
        $this->assertFalse($service->canProcessBranch($service->findForDetail(1)));
        $this->assertTrue($service->canProcessBranch($service->findForDetail(3)));
    }

    public function test_super_admin_keeps_company_wide_leave_visibility(): void
    {
        session([
            'hr_user_id' => 99,
            'hr_user_level' => 3,
            'hr_branch_id' => 10,
            'companies_groups_id' => 1,
        ]);

        $this->assertInstanceOf(EmployeeVacation::class, app(LeaveRequestService::class)->findForDetail(2));
    }
}
