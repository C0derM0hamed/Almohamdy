<?php

namespace Tests\Unit;

use App\Repositories\Complaints\ComplaintReplyRepository;
use App\Repositories\Complaints\ComplaintRepository;
use App\Repositories\Complaints\ComplaintStatusRepository;
use App\Services\Complaints\ComplaintService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ComplaintTimelineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');

        Schema::create('complaints_status', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('info')->nullable();
        });
        Schema::create('ra_users', function (Blueprint $table): void {
            $table->integer('hr_id')->primary();
            $table->string('hr_first_name');
            $table->string('hr_last_name');
        });
        Schema::create('complaints_reply', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('complaints_id');
            $table->integer('complaint_status_id');
            $table->integer('created_by');
            $table->timestamp('created_at')->nullable();
            $table->text('details')->nullable();
            $table->string('defendant')->nullable();
            $table->string('defendant_job')->nullable();
        });
    }

    public function test_timeline_keeps_repeated_status_events_in_legacy_order(): void
    {
        app('db')->table('complaints_status')->insert([
            'id' => 3, 'name_en' => 'In progress', 'name_ar' => 'تحت الإجراء', 'info' => '#aaa',
        ]);
        app('db')->table('ra_users')->insert([
            'hr_id' => 10, 'hr_first_name' => 'Test', 'hr_last_name' => 'User',
        ]);
        app('db')->table('complaints_reply')->insert([
            ['id' => 1, 'complaints_id' => 50, 'complaint_status_id' => 3, 'created_by' => 10, 'created_at' => '2026-01-01 10:00:00', 'details' => 'first'],
            ['id' => 2, 'complaints_id' => 50, 'complaint_status_id' => 3, 'created_by' => 10, 'created_at' => '2026-01-01 11:00:00', 'details' => 'second'],
        ]);

        $service = new ComplaintService(
            new ComplaintRepository,
            new ComplaintReplyRepository,
            new ComplaintStatusRepository,
        );

        $timeline = $service->timeline(50);

        $this->assertCount(2, $timeline);
        $this->assertSame('first', $timeline[0]['reply']->details);
        $this->assertSame('second', $timeline[1]['reply']->details);
    }
}
