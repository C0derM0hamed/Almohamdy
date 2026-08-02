<?php

namespace Tests\Feature;

use App\Services\EmergencyReception\EmergencyReceptionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EmergencyReceptionWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');
        Schema::create('escape_report_form', function (Blueprint $b): void {
            $b->increments('id'); $b->integer('branch_id'); $b->integer('companies_groups_id'); $b->integer('user_id');
            foreach (['father_name','father_nationality','father_idno','father_mobile','mother_name','mother_nationality','mother_idno','mother_mobile','entery_date','born_date','child_gender','escape_date','reporter_side','date','type'] as $column) $b->string($column)->nullable();
        });
        session(['hr_user_id' => 10, 'hr_user_level' => 2, 'hr_branch_id' => 1, 'companies_groups_id' => 1]);
    }

    public function test_all_five_database_workflows_have_verified_definitions(): void
    {
        $service = app(EmergencyReceptionService::class);
        foreach (EmergencyReceptionService::TYPES as $type) {
            $definition = $service->definition($type);
            $this->assertNotEmpty($definition['title']);
            $this->assertNotEmpty($definition['fields']);
            $this->assertNotEmpty($definition['columns']);
        }
    }

    public function test_escape_record_creation_is_branch_and_company_scoped(): void
    {
        $service = app(EmergencyReceptionService::class);
        $id = $service->create('escape', ['mother_name' => 'مدققة', 'mother_nationality' => 1, 'mother_idno' => '1000000000', 'mother_mobile' => '0500000000', 'entery_date' => '2026-08-01', 'born_date' => '2026-08-01', 'child_gender' => 1, 'escape_date' => '2026-08-02', 'reporter_side' => 'الشرطة', 'type' => 1]);
        $this->assertSame('مدققة', $service->find('escape', $id)->mother_name);
        session(['companies_groups_id' => 2]);
        $this->assertNull($service->find('escape', $id));
    }

    public function test_other_branches_and_super_admin_are_denied(): void
    {
        $service = app(EmergencyReceptionService::class);
        foreach ([[2, 2], [1, 3]] as [$branch, $level]) {
            session(['hr_branch_id' => $branch, 'hr_user_level' => $level]);
            try { $service->list('escape', []); $this->fail('Expected access denial'); }
            catch (HttpException $exception) { $this->assertSame(403, $exception->getStatusCode()); }
        }
    }
}
