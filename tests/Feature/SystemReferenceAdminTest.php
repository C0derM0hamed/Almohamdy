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
        Schema::create('job_titles', function (Blueprint $b): void { $b->increments('id'); $b->integer('branch_id'); $b->string('name_en')->nullable(); $b->string('name_ar')->nullable(); $b->string('info')->nullable(); $b->integer('training_declarations_id')->nullable(); $b->integer('publish')->default(1); });
        foreach (['complaint_closing_reasons', 'complaint_letter_receiver', 'complaints_status'] as $table) Schema::create($table, function (Blueprint $b): void { $b->increments('id'); $b->string('name_en')->nullable(); $b->string('name_ar')->nullable(); $b->string('name_ch')->nullable(); $b->integer('publish')->default(1); });
        Schema::create('medical_terminology', function (Blueprint $b): void { $b->increments('id'); $b->string('name_en')->nullable(); $b->string('name_ar')->nullable(); $b->string('name_ch')->nullable(); $b->string('info')->nullable(); $b->integer('publish')->default(1); });
        Schema::create('services_codes', function (Blueprint $b): void { $b->increments('id'); $b->string('name_en')->nullable(); $b->string('name_ar')->nullable(); $b->string('price')->nullable(); $b->string('code')->nullable(); $b->integer('publish')->default(1); });
        DB::table('user_groups')->insert(['name_en' => 'Group', 'name_ar' => 'مجموعة', 'name_ch' => '', 'publish' => 1]);
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
        session(['hr_branch_id' => 2]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->find('job-titles', $job);
    }
}
