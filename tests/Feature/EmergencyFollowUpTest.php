<?php

namespace Tests\Feature;

use App\Services\EmergencyFollowUp\EmergencyFollowUpService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmergencyFollowUpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');

        Schema::create('emergency_follow_up', function (Blueprint $table): void {
            $table->increments('id'); $table->string('date'); $table->integer('branch_id'); $table->integer('file_number');
            $table->integer('notice'); $table->string('description')->nullable(); $table->integer('notice_type');
            $table->string('action')->nullable(); $table->integer('status'); $table->integer('created_by');
            $table->integer('updated_by')->nullable(); $table->string('updated_at')->nullable();
        });
        Schema::create('emergency_follow_up_notice', function (Blueprint $table): void {
            $table->increments('id'); $table->integer('emergency_follow_up_id'); $table->text('notice');
            $table->timestamp('created_at')->nullable(); $table->integer('created_by');
        });
        Schema::create('notice_type', function (Blueprint $table): void {
            $table->increments('id'); $table->integer('branch_id'); $table->string('name_ar'); $table->string('name_en');
            $table->integer('publish')->default(1);
        });
        Schema::create('ra_users', function (Blueprint $table): void {
            $table->increments('hr_id'); $table->string('hr_first_name')->nullable(); $table->string('hr_last_name')->nullable();
        });
        DB::table('ra_users')->insert(['hr_id' => 10, 'hr_first_name' => 'مدقق', 'hr_last_name' => 'الطوارئ']);
        DB::table('notice_type')->insert(['id' => 1, 'branch_id' => 1, 'name_ar' => 'إدارية', 'name_en' => 'Administrative', 'publish' => 1]);
        DB::table('emergency_follow_up')->insert(['id' => 1, 'date' => (string) time(), 'branch_id' => 1, 'file_number' => 123, 'notice' => 1, 'description' => 'وصف', 'notice_type' => 1, 'action' => 'إجراء', 'status' => 2, 'created_by' => 10]);
        session(['hr_user_id' => 10, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
    }

    public function test_follow_up_workflow_is_scoped_and_keeps_notice_history(): void
    {
        $service = app(EmergencyFollowUpService::class);
        $this->assertSame(1, $service->listOpen()->total());
        $created = $service->create(['file_number' => 456, 'notice' => 1, 'description' => 'وصف جديد', 'notice_type' => 2, 'action' => 'إجراء جديد', 'status' => 2]);
        $service->addNotice($created->id, 'ملاحظة متابعة');
        $service->close($created->id);
        $this->assertSame(1, (int) DB::table('emergency_follow_up')->where('id', $created->id)->value('status'));
        $this->assertDatabaseHas('emergency_follow_up_notice', ['emergency_follow_up_id' => $created->id, 'notice' => 'ملاحظة متابعة']);
    }

    public function test_other_branch_cannot_read_or_modify_follow_up(): void
    {
        session(['hr_branch_id' => 2]);
        $service = app(EmergencyFollowUpService::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->find(1);
    }
}
