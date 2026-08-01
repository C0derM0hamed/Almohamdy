<?php

namespace Tests\Feature;

use App\Services\Transferal\TransferalService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransferalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');
        foreach (['companies_groups', 'specialization', 'transferal_reason', 'room_type', 'rep1_payment_type'] as $table) {
            Schema::create($table, function (Blueprint $blueprint) use ($table): void { $blueprint->increments('id'); $blueprint->string('name_ar')->nullable(); if ($table === 'companies_groups') $blueprint->string('name_en')->nullable(); $blueprint->tinyInteger('publish')->default(1); });
        }
        Schema::create('transferal', function (Blueprint $table): void { $table->increments('id'); $table->integer('branch_id'); $table->integer('companies_groups_id'); $table->integer('transferal_from'); $table->integer('transferal_to'); $table->string('patient_name'); $table->string('file_number'); $table->string('idno'); $table->integer('specialization'); $table->integer('transferal_reason'); $table->integer('room_type'); $table->integer('payment_type'); $table->string('referring_doctor'); $table->string('file')->nullable(); $table->string('date'); $table->integer('created_by'); });
        foreach (['transferal_confirm', 'transferal_approval', 'transferal_refused', 'transferal_receive_confirmation'] as $table) {
            Schema::create($table, function (Blueprint $blueprint) use ($table): void { $blueprint->increments('id'); $blueprint->integer('branch_id'); $blueprint->integer('companies_groups_id'); $blueprint->integer('transferal_id'); $blueprint->string('doctor')->nullable(); $blueprint->integer('room_type')->nullable(); $blueprint->string('bed_room_number')->nullable(); $blueprint->string('refusal_reason')->nullable(); $blueprint->string('date')->nullable(); $blueprint->string('file')->nullable(); $blueprint->timestamp('created_at')->nullable(); $blueprint->integer('created_by'); });
        }
        DB::table('companies_groups')->insert([['id' => 1, 'name_ar' => 'الأول', 'name_en' => 'One'], ['id' => 2, 'name_ar' => 'الثاني', 'name_en' => 'Two']]);
        foreach (['specialization', 'transferal_reason', 'room_type', 'rep1_payment_type'] as $table) DB::table($table)->insert(['id' => 1, 'name_ar' => 'اختيار']);
        session(['hr_user_id' => 10, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
        Storage::fake('public');
    }

    public function test_transfer_workflow_is_scoped_and_supports_reception_actions(): void
    {
        $service = app(TransferalService::class);
        $id = $service->create(['transferal_to' => 2, 'patient_name' => 'مريض', 'file_number' => 'T-1', 'idno' => '123', 'specialization' => 1, 'transferal_reason' => 1, 'room_type' => 1, 'payment_type' => 1, 'referring_doctor' => 'طبيب'], UploadedFile::fake()->create('request.pdf'));
        $this->assertSame(1, $service->outgoing(['file_number' => '', 'from' => '', 'to' => ''])->total());
        $service->confirm($id, ['date_time' => '2026-08-02 10:00'], null);
        session(['companies_groups_id' => 2]);
        $record = $service->find($id);
        $this->assertSame('مريض', $record->patient_name);
        $service->approve($id, ['doctor' => 'مستلم', 'room_type' => 1, 'bed_room_number' => 'B1'], null);
        $service->receive($id, ['doctor' => 'مستلم', 'date_time' => '2026-08-02 12:00'], null);
        $this->assertDatabaseHas('transferal_receive_confirmation', ['transferal_id' => $id]);
    }

    public function test_other_branch_is_forbidden(): void
    {
        session(['hr_branch_id' => 2]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(TransferalService::class)->outgoing(['file_number' => '', 'from' => '', 'to' => '']);
    }
}
