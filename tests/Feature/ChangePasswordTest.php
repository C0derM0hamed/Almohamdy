<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        config(['hm.permissions.bypass' => true]);
        app('db')->purge('sqlite');
        Schema::create('ra_users', function (Blueprint $table): void {
            $table->increments('hr_id');
            $table->string('hr_first_name')->nullable();
            $table->string('hr_last_name')->nullable();
            $table->string('hr_email_address')->nullable();
            $table->string('hr_username')->nullable();
            $table->string('hr_password');
            $table->string('hr_user_level')->default('3');
            $table->unsignedInteger('branch_id')->default(1);
            $table->string('mobile')->nullable();
            $table->unsignedInteger('companies_groups_id')->default(1);
            $table->unsignedInteger('groupid')->default(0);
            $table->timestamp('hr_last_login')->nullable();
            $table->string('activated')->default('1');
            $table->unsignedInteger('lastPassChange')->nullable();
            $table->string('department_supervisor')->nullable();
            $table->string('job_title')->nullable();
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('last_failed_login')->nullable();
        });
        DB::table('ra_users')->insert(['hr_id' => 7, 'hr_username' => 'audit', 'hr_password' => hash('sha256', 'old-pass'), 'activated' => '1']);
        $this->withSession(['hr_user_id' => 7, 'hr_user_level' => 3, 'companies_groups_id' => 1, 'hr_branch_id' => 1, 'groupid' => 0, 'hm_permissions' => []]);
    }

    public function test_current_password_is_required_and_new_password_is_written(): void
    {
        $this->post(route('profile.password.update'), [
            'current_password' => 'wrong-pass', 'password' => 'new-pass', 'password_confirmation' => 'new-pass',
        ])->assertSessionHasErrors('current_password');

        $this->post(route('profile.password.update'), [
            'current_password' => 'old-pass', 'password' => 'new-pass', 'password_confirmation' => 'new-pass',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertSame(hash('sha256', 'new-pass'), DB::table('ra_users')->where('hr_id', 7)->value('hr_password'));
    }
}
