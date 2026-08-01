<?php

namespace Tests\Feature;

use App\Support\Training\TrainingPermissions;
use Illuminate\Support\Facades\DB;

class TrainingCoordinationTest extends TrainingManagementTest
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::table('user_permission')->insert(['userid' => 1, 'page' => TrainingPermissions::COORDINATION, 'permit' => '2']);
    }

    public function test_coordination_list_detail_and_permissions_are_scoped(): void
    {
        $session = $this->sessionFor(1);
        $this->withSession($session)->get(route('modules.training.coordination.index'))->assertOk()->assertSee('PW_AUDIT_TRAINEE')->assertDontSee('PW_AUDIT_OTHER');
        $this->withSession($session)->get(route('modules.training.coordination.show', 20))->assertNotFound();
        $this->withSession($session)->get(route('modules.training.coordination.document', [10, 'plan']))->assertOk();

        DB::table('user_permission')->where('page', TrainingPermissions::COORDINATION)->delete();
        $this->withSession([...$session, 'hm_permissions' => []])->get(route('modules.training.coordination.index'))->assertForbidden();
    }

    public function test_coordination_creates_and_updates_type_one_statuses(): void
    {
        $session = $this->sessionFor(1);
        $this->withSession($session)->post(route('modules.training.coordination.store'), [
            'employee_id' => 2,
            'training_coordinator' => 1,
            'begin_date' => '2026-08-04',
            'time_from' => '08:00',
            'time_to' => '16:00',
        ])->assertRedirect();

        $created = DB::table('training_confirmation')->where('employee_id', 2)->latest('id')->first();
        $this->assertSame(1, $created->status);

        $this->withSession($session)->post(route('modules.training.coordination.status', $created->id), [
            'status_id' => 2,
        ])->assertRedirect();
        $this->assertDatabaseHas('training_confirmation', ['id' => $created->id, 'status' => 2]);

        $this->withSession($session)->post(route('modules.training.coordination.status', $created->id), [
            'status_id' => 3,
            'acknowledgement' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('training_confirmation', ['id' => $created->id, 'status' => 3]);
    }
}
