<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');
        Schema::create('user_permission', function (Blueprint $b): void { $b->increments('id'); $b->integer('userid'); $b->string('page')->nullable(); $b->string('permit')->nullable(); });
        Schema::create('user_groups_permission', function (Blueprint $b): void { $b->increments('id'); $b->integer('groupid'); $b->string('page')->nullable(); $b->string('permit')->nullable(); });
    }

    public function test_settings_landing_uses_current_authorization(): void
    {
        $this->withoutMiddleware();
        $this->withSession(['hr_user_id' => 10, 'hr_user_level' => 2, 'hr_branch_id' => 1, 'companies_groups_id' => 1])->get('/modules/settings')->assertOk()->assertSee(__('settings.no_access'));
        $this->withSession(['hr_user_id' => 10, 'hr_user_level' => 3, 'hr_branch_id' => 1, 'companies_groups_id' => 1])->get('/modules/settings')->assertOk()->assertSee(__('settings.company_groups'));
    }
}
