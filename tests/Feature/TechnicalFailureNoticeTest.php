<?php

namespace Tests\Feature;

use App\Services\TechnicalFailure\TechnicalFailureService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TechnicalFailureNoticeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'technical_failure_notice_process', 'technical_failure_notice_status',
            'technical_failure_notice_platform', 'technical_failure_notice_service_type',
            'technical_failure_notice_sections', 'technical_failure_notice_type',
            'technical_failure_notice', 'ra_users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('technical_failure_notice', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('branch_id');
            $table->integer('companies_groups_id');
            $table->integer('user_id');
            $table->string('date_time');
            $table->integer('technical_failure_notice_sections')->default(0);
            $table->integer('technical_failure_notice_type')->default(0);
            $table->integer('technical_failure_notice_platform')->default(0);
            $table->integer('technical_failure_notice_service_type')->default(0);
            $table->string('other')->nullable();
            $table->string('notice')->nullable();
            $table->integer('status')->default(0);
            $table->string('file_name')->nullable();
        });
        Schema::create('technical_failure_notice_process', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('technical_failure_notice_id');
            $table->integer('status_id');
            $table->integer('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        foreach (['technical_failure_notice_status', 'technical_failure_notice_platform', 'technical_failure_notice_service_type', 'technical_failure_notice_sections', 'technical_failure_notice_type'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name_ar')->nullable();
                $table->string('name_en')->nullable();
                $table->string('info')->nullable();
                $table->integer('publish')->default(1);
                $table->integer('branch_id')->default(0);
            });
        }
        Schema::create('ra_users', function (Blueprint $table): void {
            $table->increments('hr_id');
            $table->string('hr_first_name')->nullable();
            $table->string('hr_last_name')->nullable();
        });

        DB::table('technical_failure_notice_status')->insert(['id' => 1, 'name_ar' => 'قيد المعالجة', 'name_en' => 'Processing', 'info' => '#0d6efd']);
        DB::table('technical_failure_notice_platform')->insert(['id' => 1, 'name_ar' => 'منصة الاختبار', 'name_en' => 'Test platform']);
        DB::table('technical_failure_notice_service_type')->insert(['id' => 1, 'name_ar' => 'خدمة الاختبار', 'name_en' => 'Test service']);
        DB::table('ra_users')->insert(['hr_id' => 10, 'hr_first_name' => 'موظف', 'hr_last_name' => 'الاختبار']);
        DB::table('technical_failure_notice')->insert([
            ['id' => 1, 'branch_id' => 10, 'companies_groups_id' => 1, 'user_id' => 10, 'date_time' => (string) time(), 'notice' => 'بلاغ الفرع الحالي'],
            ['id' => 2, 'branch_id' => 20, 'companies_groups_id' => 1, 'user_id' => 10, 'date_time' => (string) time(), 'notice' => 'بلاغ فرع آخر'],
            ['id' => 3, 'branch_id' => 10, 'companies_groups_id' => 2, 'user_id' => 10, 'date_time' => (string) time(), 'notice' => 'بلاغ شركة أخرى'],
        ]);
    }

    public function test_branch_scope_blocks_idor_and_status_updates_are_logged(): void
    {
        session([
            'hr_user_id' => 10,
            'hr_user_level' => 1,
            'hr_branch_id' => 10,
            'companies_groups_id' => 1,
        ]);

        $service = app(TechnicalFailureService::class);

        $this->assertNotNull($service->find(1));
        $this->assertNull($service->find(2));
        $this->assertNull($service->find(3));

        $service->updateStatus(1, 1);

        $this->assertSame(1, (int) DB::table('technical_failure_notice')->where('id', 1)->value('status'));
        $this->assertSame(1, DB::table('technical_failure_notice_process')->where('technical_failure_notice_id', 1)->count());
    }
}
