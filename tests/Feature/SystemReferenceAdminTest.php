<?php

namespace Tests\Feature;

use App\Services\SystemAdministration\ReferenceAdminService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SystemReferenceAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp(); config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']); app('db')->purge('sqlite');
        Schema::create('user_groups', function (Blueprint $b): void { $b->increments('id'); $b->string('name_en')->nullable(); $b->string('name_ar')->nullable(); $b->string('name_ch')->nullable(); $b->integer('publish')->default(1); });
        Schema::create('companies_groups', function (Blueprint $b): void { $b->increments('id'); $b->string('name_ar')->nullable(); $b->integer('publish')->default(1); });
        Schema::create('branches', function (Blueprint $b): void { $b->increments('id'); $b->unsignedInteger('companies_groups_id'); $b->string('name_ar')->nullable(); $b->integer('publish')->default(1); });
        Schema::create('job_titles', function (Blueprint $b): void { $b->increments('id'); $b->integer('branch_id'); $b->string('name_en')->nullable(); $b->string('name_ar')->nullable(); $b->string('info')->nullable(); $b->integer('training_declarations_id')->nullable(); $b->integer('publish')->default(1); });
        Schema::create('branches_area', function (Blueprint $b): void { $b->increments('id'); $b->unsignedInteger('branch_id'); $b->string('name_en')->nullable(); $b->string('name_ar')->nullable(); $b->string('name_ch')->nullable(); $b->string('info')->nullable(); $b->integer('publish')->default(1); });
        Schema::create('inquiries', function (Blueprint $b): void { $b->increments('id'); $b->unsignedInteger('branch_id'); $b->string('name_en')->nullable(); $b->string('name_ar')->nullable(); $b->string('name_ch')->nullable(); $b->string('info')->nullable(); $b->integer('publish')->default(1); });
        foreach (['complaint_closing_reasons', 'complaint_letter_receiver', 'complaints_status'] as $table) Schema::create($table, function (Blueprint $b): void { $b->increments('id'); $b->string('name_en')->nullable(); $b->string('name_ar')->nullable(); $b->string('name_ch')->nullable(); $b->integer('publish')->default(1); });
        Schema::create('medical_terminology', function (Blueprint $b): void { $b->increments('id'); $b->string('name_en')->nullable(); $b->string('name_ar')->nullable(); $b->string('name_ch')->nullable(); $b->string('info')->nullable(); $b->integer('publish')->default(1); });
        Schema::create('services_codes', function (Blueprint $b): void { $b->increments('id'); $b->string('name_en')->nullable(); $b->string('name_ar')->nullable(); $b->string('price')->nullable(); $b->string('code')->nullable(); $b->integer('publish')->default(1); });
        DB::table('user_groups')->insert(['name_en' => 'Group', 'name_ar' => 'مجموعة', 'name_ch' => '', 'publish' => 1]);
        DB::table('companies_groups')->insert(['id' => 1, 'name_ar' => 'الشركة', 'publish' => 1]);
        DB::table('branches')->insert([
            ['id' => 1, 'companies_groups_id' => 1, 'name_ar' => 'الفرع الأول', 'publish' => 1],
            ['id' => 2, 'companies_groups_id' => 1, 'name_ar' => 'الفرع الثاني', 'publish' => 1],
        ]);
        session(['hr_user_id' => 10, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
    }

    public function test_reference_crud_and_branch_scope(): void
    {
        $service = app(ReferenceAdminService::class);
        $id = $service->create('groups', ['name_en' => 'New', 'name_ar' => 'جديد', 'name_ch' => 'N']);
        $service->toggle('groups', $id);
        $this->assertDatabaseHas('user_groups', ['id' => $id, 'publish' => 0]);
        $job = $service->create('job-titles', ['branch_id' => 1, 'name_en' => 'Nurse', 'name_ar' => 'تمريض', 'info' => '']);
        $this->assertSame('تمريض', $service->find('job-titles', $job)->name_ar);
        $reason = $service->create('complaint-closing-reasons', ['name_en' => 'Closed', 'name_ar' => 'مغلق', 'name_ch' => 'C']);
        $this->assertSame('مغلق', $service->find('complaint-closing-reasons', $reason)->name_ar);
        $term = $service->create('medical-terminology', ['name_en' => 'Term', 'name_ar' => 'مصطلح', 'name_ch' => 'T', 'info' => '']);
        $this->assertSame('مصطلح', $service->find('medical-terminology', $term)->name_ar);

        $workArea = $service->create('work-areas', [
            'branch_id' => 1,
            'name_en' => 'Reception',
            'name_ar' => 'الاستقبال',
            'name_ch' => '接待',
            'info' => 'Main reception',
        ]);
        $this->assertSame('الاستقبال', $service->find('work-areas', $workArea)->name_ar);

        $inquiry = $service->create('inquiries', [
            'branch_id' => 1,
            'name_en' => 'Appointment inquiry',
            'name_ar' => 'استفسار موعد',
            'name_ch' => '预约咨询',
            'info' => 'Administrative inquiry type',
        ]);
        $this->assertSame('استفسار موعد', $service->find('inquiries', $inquiry)->name_ar);

        $this->assertCount(1, $service->list('work-areas')->items());
        $this->assertCount(1, $service->list('inquiries')->items());

        session(['hr_branch_id' => 2]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->find('work-areas', $workArea);
    }

    public function test_reference_branch_scopes_reject_other_company_branches(): void
    {
        DB::table('branches')->insert(['id' => 3, 'companies_groups_id' => 2, 'name_ar' => 'شركة أخرى', 'publish' => 1]);
        DB::table('branches_area')->insert(['branch_id' => 3, 'name_en' => 'Other', 'name_ar' => 'أخرى', 'name_ch' => '', 'info' => '', 'publish' => 1]);

        $this->assertCount(0, app(ReferenceAdminService::class)->list('work-areas')->items());
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ReferenceAdminService::class)->create('work-areas', [
            'branch_id' => 3,
            'name_en' => 'Cross company',
            'name_ar' => 'خارج الشركة',
        ]);
    }
}
