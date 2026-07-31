<?php

namespace Tests\Unit;

use App\Models\InquiryAndService;
use App\Repositories\Inquiries\InquiryAndServiceRepository;
use App\Services\Inquiries\InquiryAndServiceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InquiryLegacyCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');
        session()->start();
        session(['companies_groups_id' => 1, 'hr_branch_id' => 2, 'hr_user_id' => 10]);

        Schema::create('inquiries_and_services', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('date');
            $table->integer('branch_id');
            $table->integer('job_title_sender')->nullable();
            $table->string('enquirer');
            $table->integer('inquired_section');
            $table->string('mobile');
            $table->integer('inquiry_id')->default(0);
            $table->string('inquiry_details')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at')->nullable();
            $table->integer('companies_groups_id');
            $table->integer('status')->default(999999);
            $table->integer('job_title')->nullable();
            $table->integer('noAnswerTimes')->default(0);
        });
        Schema::create('inquiries_and_services_reply', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('inquiries_and_services_id');
            $table->integer('inquiry_status_id');
            $table->integer('branch_id')->nullable();
            $table->integer('inquired_section')->default(0);
            $table->integer('inquiry_id')->default(0);
            $table->string('inquiry_details')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_department_forward_uses_only_legacy_columns(): void
    {
        app('db')->table('inquiries_and_services')->insert([
            'id' => 7, 'date' => '1750000000', 'branch_id' => 2, 'enquirer' => 'Patient',
            'inquired_section' => 3, 'mobile' => '0500000000', 'created_by' => 10,
            'companies_groups_id' => 1, 'status' => 3,
        ]);

        $repository = new InquiryAndServiceRepository;
        $inquiry = InquiryAndService::query()->findOrFail(7);
        $repository->applyStatusUpdate($inquiry, [
            'status_id' => 999999,
            'notes' => 'forward',
            'department_id' => 8,
            'assignment_type' => 'department',
            'timeline_message' => 'forward',
        ]);

        $this->assertDatabaseHas('inquiries_and_services', [
            'id' => 7, 'status' => 999999, 'inquired_section' => 8,
        ]);
        $this->assertDatabaseHas('inquiries_and_services_reply', [
            'inquiries_and_services_id' => 7, 'inquiry_status_id' => 999999,
            'inquired_section' => 8, 'inquiry_details' => 'forward',
        ]);
    }

    public function test_only_verified_legacy_statuses_are_offered(): void
    {
        $this->assertSame(
            [3, 4, 5, 999999],
            array_column((new InquiryAndServiceRepository)->updateStatusOptions(), 'id'),
        );
    }

    public function test_outgoing_query_is_always_isolated_by_company_and_branch(): void
    {
        app('db')->table('inquiries_and_services')->insert([
            ['id' => 1, 'date' => '1750000001', 'branch_id' => 2, 'enquirer' => 'Visible', 'inquired_section' => 3, 'mobile' => '1', 'created_by' => 10, 'companies_groups_id' => 1],
            ['id' => 2, 'date' => '1750000002', 'branch_id' => 9, 'enquirer' => 'Other branch', 'inquired_section' => 3, 'mobile' => '2', 'created_by' => 10, 'companies_groups_id' => 1],
            ['id' => 3, 'date' => '1750000003', 'branch_id' => 2, 'enquirer' => 'Other company', 'inquired_section' => 3, 'mobile' => '3', 'created_by' => 10, 'companies_groups_id' => 4],
        ]);

        $records = (new InquiryAndServiceRepository)->scopedQuery('outgoing')->get();

        $this->assertSame([1], $records->pluck('id')->all());
    }

    public function test_successful_contact_is_the_only_legacy_locked_status(): void
    {
        $service = new InquiryAndServiceService(new InquiryAndServiceRepository);

        foreach ([0, 1, 3, 5, 999999] as $status) {
            $inquiry = (new InquiryAndService)->forceFill(['status' => $status]);
            $this->assertTrue($service->canUpdateStatus($inquiry));
        }

        $closedInquiry = (new InquiryAndService)->forceFill(['status' => 4]);
        $this->assertFalse($service->canUpdateStatus($closedInquiry));
    }
}
