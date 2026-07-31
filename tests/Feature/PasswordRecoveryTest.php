<?php

namespace Tests\Feature;

use App\Services\Sms\SmsGateway;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    private RecoverySmsFake $sms;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        app('db')->purge('sqlite');
        Schema::create('ra_users', function (Blueprint $table): void {
            $table->increments('hr_id');
            $table->string('hr_username')->unique();
            $table->string('mobile');
            $table->string('hr_password');
            $table->string('activated')->default('1');
            $table->string('lastPassChange')->nullable();
        });

        app('db')->table('ra_users')->insert([
            'hr_id' => 7,
            'hr_username' => 'PW_AUDIT_USER',
            'mobile' => '0500000000',
            'hr_password' => hash('sha256', 'old-password'),
            'activated' => '1',
            'lastPassChange' => '0',
        ]);

        $this->sms = new RecoverySmsFake;
        $this->app->instance(SmsGateway::class, $this->sms);
    }

    public function test_recovery_uses_same_generic_response_for_unknown_identity(): void
    {
        $known = $this->post('/password/forgot', [
            'username' => 'PW_AUDIT_USER', 'mobile' => '0500000000',
        ]);
        $known->assertRedirectToRoute('password.otp.show')
            ->assertSessionHas('status', __('password_recovery.sent'));

        $this->get('/password/forgot')->assertOk();
        $unknown = $this->post('/password/forgot', [
            'username' => 'PW_AUDIT_UNKNOWN', 'mobile' => '0500000000',
        ]);
        $unknown->assertRedirectToRoute('password.otp.show')
            ->assertSessionHas('status', __('password_recovery.sent'));
        $this->assertCount(1, $this->sms->messages);
    }

    public function test_otp_is_hashed_expiring_and_attempt_limited(): void
    {
        $this->post('/password/forgot', [
            'username' => 'PW_AUDIT_USER', 'mobile' => '0500000000',
        ]);

        $this->assertNotSame($this->sms->code, session('password_recovery.otp_hash'));
        $this->assertSame(0, session('password_recovery.attempts'));

        for ($i = 0; $i < 5; $i++) {
            $this->post('/password/otp', ['otp' => '000000'])->assertSessionHasErrors('otp');
        }

        $this->get('/password/reset')->assertRedirectToRoute('password.forgot');
    }

    public function test_successful_otp_allows_one_time_sha256_legacy_reset(): void
    {
        $this->post('/password/forgot', [
            'username' => 'PW_AUDIT_USER', 'mobile' => '0500000000',
        ]);

        $this->post('/password/otp', ['otp' => $this->sms->code])
            ->assertRedirectToRoute('password.reset.show');
        $this->get('/password/reset')->assertOk();

        $this->post('/password/reset', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirectToRoute('login');

        $this->assertDatabaseHas('ra_users', [
            'hr_id' => 7,
            'hr_password' => hash('sha256', 'new-password'),
        ]);
        $this->assertNotSame('0', (string) app('db')->table('ra_users')->value('lastPassChange'));
        $this->get('/password/reset')->assertRedirectToRoute('password.forgot');
    }
}

class RecoverySmsFake extends SmsGateway
{
    public array $messages = [];
    public string $code = '';

    public function send(string $mobile, string $message, ?string $sender = null): array
    {
        preg_match('/([0-9]{6})/', $message, $matches);
        $this->code = (string) ($matches[1] ?? '');
        $this->messages[] = compact('mobile', 'message');

        return ['ok' => true, 'provider' => 'fake', 'response' => null, 'error' => null];
    }
}
