<?php

namespace Tests\Feature;

use App\Services\LegacySidebarPageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacySidebarFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');

        Schema::create('birth_notification', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('branch_id');
            $table->integer('companies_groups_id');
            $table->integer('user_id');
            $table->string('newborn_file_number');
            $table->string('mother_file_number');
            $table->integer('gender');
            $table->integer('newborn_type');
            $table->integer('newborn_status');
            $table->integer('birth_status');
            $table->integer('birth_notification_obstetrics');
            $table->string('date');
            $table->integer('language')->default(1);
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('sanad_reg', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('first_no')->default(0);
            $table->integer('last_no')->default(0);
            $table->integer('branch')->default(0);
            $table->string('intered_name')->default('');
            $table->integer('date');
            $table->string('updated_name')->default('');
            $table->string('last_update');
            $table->string('status')->default('1');
            $table->string('comment')->nullable();
        });

        Schema::create('administrative_cases', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('date');
            $table->integer('branch_id');
            $table->integer('companies_groups_id');
            $table->string('claim_amount');
            $table->integer('status')->default(1);
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('administrative_cases_status', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name_ar');
            $table->integer('publish')->default(1);
        });
        Schema::create('administrative_cases_actions', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('administrative_cases_id');
            $table->integer('status_id');
            $table->integer('branch_id');
            $table->text('details')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at')->nullable();
            $table->string('administrative_cases_request_file')->nullable();
            $table->string('session_1_file')->nullable();
        });
        Schema::create('administrative_cases_statement_request', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('administrative_cases_id');
            $table->integer('branch_id');
            $table->text('details');
            $table->text('summary')->nullable();
            $table->integer('section')->nullable();
            $table->text('reply')->nullable();
            $table->string('reply_date')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at')->nullable();
            $table->string('file')->nullable();
        });
        DB::table('administrative_cases_status')->insert(['id' => 2, 'name_ar' => 'قيد المراجعة', 'publish' => 1]);

        session([
            'hr_user_id' => 10,
            'hr_username' => 'reviewer',
            'hr_branch_id' => 2,
            'companies_groups_id' => 1,
        ]);
    }

    public function test_birth_notification_and_sanad_required_metadata_are_saved(): void
    {
        $service = app(LegacySidebarPageService::class);

        $birth = $service->save('birth_notification', [
            'newborn_file_number' => 'N-1',
            'mother_file_number' => 'M-1',
            'gender' => 1,
            'newborn_type' => 1,
            'newborn_status' => 1,
            'birth_status' => 1,
            'birth_notification_obstetrics' => 7,
            'language' => 1,
        ]);
        $this->assertDatabaseHas('birth_notification', [
            'id' => $birth,
            'branch_id' => 2,
            'companies_groups_id' => 1,
            'user_id' => 10,
            'birth_notification_obstetrics' => 7,
        ]);

        $sanad = $service->save('sanad_reg', ['first_no' => 1, 'last_no' => 10, 'branch' => 2]);
        $row = DB::table('sanad_reg')->where('id', $sanad)->first();
        $this->assertSame('reviewer', $row->intered_name);
        $this->assertMatchesRegularExpression('/^\d{14}$/', $row->last_update);
    }

    public function test_updating_a_record_preserves_creation_metadata(): void
    {
        $service = app(LegacySidebarPageService::class);
        $id = $service->save('birth_notification', [
            'newborn_file_number' => 'N-2', 'mother_file_number' => 'M-2', 'gender' => 1,
            'newborn_type' => 1, 'newborn_status' => 1, 'birth_status' => 1,
            'birth_notification_obstetrics' => 7, 'language' => 1,
        ]);
        $before = DB::table('birth_notification')->where('id', $id)->first();

        session(['hr_user_id' => 11]);
        $service->save('birth_notification', [
            'newborn_file_number' => 'N-2-updated', 'mother_file_number' => 'M-2', 'gender' => 1,
            'newborn_type' => 1, 'newborn_status' => 1, 'birth_status' => 1,
            'birth_notification_obstetrics' => 7, 'language' => 1,
        ], $id);
        $after = DB::table('birth_notification')->where('id', $id)->first();

        $this->assertSame($before->user_id, $after->user_id);
        $this->assertSame($before->date, $after->date);
        $this->assertSame($before->created_at, $after->created_at);
        $this->assertSame(11, $after->updated_by);
    }

    public function test_case_action_is_scoped_logged_and_updates_parent_status(): void
    {
        $service = app(LegacySidebarPageService::class);
        $id = $service->save('administrative_cases', ['claim_amount' => '500']);

        $service->addCaseAction('administrative_cases', $id, ['status_id' => 2, 'details' => 'تمت المراجعة'], []);

        $this->assertDatabaseHas('administrative_cases_actions', [
            'administrative_cases_id' => $id,
            'status_id' => 2,
            'branch_id' => 2,
            'created_by' => 10,
            'details' => 'تمت المراجعة',
        ]);
        $this->assertDatabaseHas('administrative_cases', ['id' => $id, 'status' => 2]);
        $this->assertSame('قيد المراجعة', $service->workflowHistory('administrative_cases', $id)[0]->status_name);
    }

    public function test_case_status_dashboard_uses_the_old_scoped_status_counts_and_filters(): void
    {
        $service = app(LegacySidebarPageService::class);
        $id = $service->save('administrative_cases', ['claim_amount' => '500']);
        $service->addCaseAction('administrative_cases', $id, ['status_id' => 2, 'details' => 'تمت المراجعة'], []);

        $summary = $service->caseDashboard('administrative_cases');
        $this->assertCount(1, $summary);
        $this->assertSame('قيد المراجعة', $summary[0]->name_ar);
        $this->assertSame(1, $summary[0]->count);

        $filtered = $service->caseDashboard('administrative_cases', '', ['status' => 2]);
        $this->assertSame(1, $filtered[0]->count);
    }

    public function test_case_statement_request_and_reply_are_scoped_to_the_parent_case(): void
    {
        $service = app(LegacySidebarPageService::class);
        $id = $service->save('administrative_cases', ['claim_amount' => '500']);
        $service->addCaseStatement('administrative_cases', $id, ['details' => 'يرجى الإفادة', 'summary' => 'طلب بيانات', 'section' => 3]);
        $statement = $service->caseStatements('administrative_cases', $id)[0];
        $service->replyCaseStatement('administrative_cases', $id, $statement->id, ['reply' => 'تمت الإفادة']);

        $this->assertDatabaseHas('administrative_cases_statement_request', [
            'id' => $statement->id,
            'administrative_cases_id' => $id,
            'branch_id' => 2,
            'created_by' => 10,
            'reply' => 'تمت الإفادة',
        ]);
    }
}
