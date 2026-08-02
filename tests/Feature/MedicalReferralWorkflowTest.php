<?php

namespace Tests\Feature;

use App\Services\MedicalReferrals\MedicalReferralService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MedicalReferralWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');
        foreach (['room_type', 'booking_period', 'ehala_case_apology_type'] as $table) {
            Schema::create($table, function (Blueprint $b): void {
                $b->increments('id');
                $b->string('name_ar');
                $b->string('name_en')->nullable();
                $b->tinyInteger('publish')->default(1);
            });
        }
        Schema::create('countries', function (Blueprint $b): void {
            $b->increments('id');
            $b->string('country_nationality_ar');
            $b->tinyInteger('publish')->default(1);
        });
        Schema::create('incident_report_form_doctors', function (Blueprint $b): void {
            $b->increments('id');
            $b->string('name');
            $b->integer('companies_groups_id');
            $b->tinyInteger('publish')->default(1);
        });
        Schema::create('ra_users', function (Blueprint $b): void {
            $b->increments('id');
            $b->integer('hr_id');
            $b->integer('branch_id');
            $b->integer('companies_groups_id');
            $b->integer('hr_user_level');
            $b->string('hr_first_name');
        });
        Schema::create('bed_reservation', function (Blueprint $b): void {
            $b->increments('id');
            foreach (['branch_id', 'companies_groups_id', 'user_id', 'gender', 'room_type', 'booking_period'] as $c) {
                $b->integer($c);
            } foreach (['patient_name', 'age', 'idno', 'doctor', 'date', 'letter_side', 'lang'] as $c) {
                $b->string($c);
            }
        });
        Schema::create('accept_referral', function (Blueprint $b): void {
            $b->increments('id');
            foreach (['branch_id', 'companies_groups_id', 'user_id', 'nationality', 'room_type', 'booking_period'] as $c) {
                $b->integer($c);
            } foreach (['patient_name', 'idno', 'contact_number', 'ehala_number', 'doctor', 'date'] as $c) {
                $b->string($c);
            }
        });
        Schema::create('ehala_case_apology', function (Blueprint $b): void {
            $b->increments('id');
            foreach (['branch_id', 'companies_groups_id', 'user_id', 'nationality', 'apology'] as $c) {
                $b->integer($c);
            } foreach (['patient_name', 'idno', 'ehala_number', 'date'] as $c) {
                $b->string($c);
            }
        });
        foreach (['crisis_management', 'red_crescent'] as $table) {
            Schema::create($table, function (Blueprint $b): void {
                $b->increments('id');
                foreach (['branch_id', 'companies_groups_id', 'user_id', 'booking_period', 'room_type', 'apology'] as $c) {
                    $b->integer($c);
                } $b->string('contact_number');
                $b->string('date');
            });
        }
        Schema::create('Pulseـstatus', function (Blueprint $b): void {
            $b->increments('id');
            foreach (['doctor', 'branch_id', 'group_id', 'status', 'user_id'] as $c) {
                $b->integer($c);
            } foreach (['name', 'no', 'date_dlivry', 'Notification_date', 'create_at', 'Report_number'] as $c) {
                $b->string($c);
            }
        });
        DB::table('room_type')->insert(['id' => 1, 'name_ar' => 'العناية', 'publish' => 1]);
        DB::table('booking_period')->insert(['id' => 1, 'name_ar' => '24 ساعة', 'publish' => 1]);
        DB::table('ehala_case_apology_type')->insert(['id' => 1, 'name_ar' => 'لا يوجد سرير', 'publish' => 1]);
        DB::table('countries')->insert(['id' => 194, 'country_nationality_ar' => 'سعودي', 'publish' => 1]);
        DB::table('incident_report_form_doctors')->insert(['id' => 1, 'name' => 'طبيب', 'companies_groups_id' => 1, 'publish' => 1]);
        DB::table('ra_users')->insert(['hr_id' => 10, 'branch_id' => 1, 'companies_groups_id' => 1, 'hr_user_level' => 1, 'hr_first_name' => 'مدخل']);
        session(['hr_user_id' => 10, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
    }

    public function test_all_six_legacy_workflows_create_and_read_in_company_scope(): void
    {
        $service = app(MedicalReferralService::class);
        $records = [
            'bed-reservation' => ['patient_name' => 'مريض', 'age' => '40', 'idno' => '1000000001', 'gender' => 1, 'room_type' => 1, 'doctor' => 'طبيب', 'booking_period' => 1, 'letter_side' => 'مستشفى', 'lang' => 'ar'],
            'accept-referral' => ['patient_name' => 'مريض', 'nationality' => 194, 'idno' => '1000000001', 'contact_number' => '0500000000', 'ehala_number' => 'R-1', 'doctor' => 'طبيب', 'booking_period' => 1, 'room_type' => 1],
            'referral-apology' => ['patient_name' => 'مريض', 'nationality' => 194, 'idno' => '1000000001', 'ehala_number' => 'R-2', 'apology' => 1],
            'crisis-management' => ['contact_number' => '0500000000', 'booking_period' => 1, 'room_type' => 1, 'apology' => 1],
            'red-crescent' => ['booking_period' => 1, 'room_type' => 1, 'apology' => 1],
            'pulse-status' => ['name' => 'مريض', 'no' => '1000000001', 'doctor' => 1, 'date_dlivry' => '2026-08-02T10:00', 'Notification_date' => '2026-08-02T10:30', 'Report_number' => 'P-1'],
        ];
        foreach ($records as $type => $payload) {
            $id = $service->create($type, $payload);
            $this->assertNotNull($service->find($type, $id), $type);
        }
        session(['companies_groups_id' => 2]);
        foreach ($records as $type => $_) {
            $this->assertNull($service->find($type, 1), $type);
        }
    }

    public function test_branch_one_and_owner_only_delete_are_enforced(): void
    {
        $service = app(MedicalReferralService::class);
        DB::table('bed_reservation')->insert(['id' => 1, 'branch_id' => 1, 'companies_groups_id' => 1, 'user_id' => 11, 'patient_name' => 'مريض', 'age' => '40', 'idno' => '1000000001', 'gender' => 1, 'room_type' => 1, 'doctor' => 'طبيب', 'date' => '1', 'booking_period' => 1, 'letter_side' => 'مستشفى', 'lang' => 'ar']);
        try {
            $service->delete('bed-reservation', 1);
            $this->fail('Expected owner protection');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        session(['hr_branch_id' => 2]);
        $this->expectException(HttpException::class);
        $service->list('bed-reservation', ['from' => '', 'to' => '', 'identity' => '', 'room_type' => 0, 'user_id' => 0, 'apology' => 0]);
    }
}
