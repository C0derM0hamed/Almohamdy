<?php

namespace Tests\Feature;

use App\Mail\LoginOtpMail;
use App\Services\Sms\SmsGateway;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginOtpTest extends TestCase
{
    private LoginOtpSmsFake $sms;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'hm.permissions.bypass' => true,
        ]);
        app('db')->purge('sqlite');

        Schema::create('ra_users', function (Blueprint $table): void {
            $table->increments('hr_id');
            $table->string('hr_first_name')->nullable();
            $table->string('hr_last_name')->nullable();
            $table->string('hr_email_address')->nullable();
            $table->string('hr_username')->unique();
            $table->string('hr_password');
            $table->string('hr_user_level')->default('3');
            $table->unsignedInteger('branch_id')->default(1);
            $table->string('mobile')->nullable();
            $table->unsignedInteger('companies_groups_id')->default(1);
            $table->unsignedInteger('groupid')->default(0);
            $table->timestamp('hr_last_login')->nullable();
            $table->string('activated')->default('1');
            $table->string('lastPassChange')->nullable();
            $table->string('department_supervisor')->nullable();
            $table->string('job_title')->nullable();
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('last_failed_login')->nullable();
        });
        Schema::create('user_permission', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('userid');
            $table->string('page');
            $table->string('permit');
        });
        Schema::create('user_groups_permission', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('groupid');
            $table->string('page');
            $table->string('permit');
        });

        $this->makeUser([
            'hr_id' => 1,
            'hr_username' => 'LOGIN_TEST_USER',
            'hr_email_address' => 'login.test@example.test',
            'mobile' => '0500000001',
            'activated' => '1',
        ]);
        $this->makeUser([
            'hr_id' => 2,
            'hr_username' => 'LOGIN_TEST_INACTIVE',
            'hr_email_address' => 'inactive.test@example.test',
            'mobile' => '0500000002',
            'activated' => '0',
        ]);

        $this->sms = new LoginOtpSmsFake;
        $this->app->instance(SmsGateway::class, $this->sms);
    }

    private function makeUser(array $overrides): void
    {
        app('db')->table('ra_users')->insert(array_merge([
            'hr_first_name' => 'Test',
            'hr_last_name' => 'User',
            'hr_password' => hash('sha256', 'secret-pass'),
            'hr_user_level' => '3',
            'branch_id' => 1,
            'companies_groups_id' => 1,
            'groupid' => 0,
            'activated' => '1',
            'failed_login_attempts' => 0,
        ], $overrides));
    }

    public function test_username_password_login_is_unaffected_and_still_uses_email_channel(): void
    {
        Mail::fake();

        $this->post('/login', ['username' => 'LOGIN_TEST_USER', 'password' => 'secret-pass'])
            ->assertOk();

        $this->assertSame('email', session('otp_channel'));
        $this->assertCount(0, $this->sms->messages);
        Mail::assertSent(LoginOtpMail::class);
    }

    public function test_mobile_number_login_sends_sms_otp_and_reaches_dashboard(): void
    {
        $this->post('/login', ['username' => '0500000001', 'password' => 'secret-pass'])
            ->assertOk();

        $this->assertSame('sms', session('otp_channel'));
        $this->assertCount(1, $this->sms->messages);
        $this->assertNotSame('', $this->sms->code);

        $digits = str_split($this->sms->code);
        $payload = [];
        foreach ($digits as $i => $digit) {
            $payload['n'.($i + 1)] = $digit;
        }

        $this->post('/otp', $payload)->assertOk();
        $this->assertTrue(session()->has('hr_user_id'));
        $this->assertSame(1, session('hr_user_id'));
    }

    public function test_email_identifier_login_sends_email_otp_and_reaches_dashboard(): void
    {
        Mail::fake();

        $this->post('/login', ['username' => 'login.test@example.test', 'password' => 'secret-pass'])
            ->assertOk();

        $this->assertSame('email', session('otp_channel'));

        $code = null;
        Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $digits = str_split((string) $code);
        $payload = [];
        foreach ($digits as $i => $digit) {
            $payload['n'.($i + 1)] = $digit;
        }

        $this->post('/otp', $payload)->assertOk();
        $this->assertTrue(session()->has('hr_user_id'));
    }

    public function test_wrong_otp_is_rejected(): void
    {
        $this->post('/login', ['username' => '0500000001', 'password' => 'secret-pass']);

        $wrong = str_repeat('9', strlen($this->sms->code)) === $this->sms->code ? str_repeat('1', strlen($this->sms->code)) : str_repeat('9', strlen($this->sms->code));
        $digits = str_split($wrong);
        $payload = [];
        foreach ($digits as $i => $digit) {
            $payload['n'.($i + 1)] = $digit;
        }

        $this->post('/otp', $payload)->assertSessionHasErrors('otp');
        $this->assertFalse(session()->has('hr_user_id'));
    }

    public function test_expired_otp_is_rejected(): void
    {
        $this->post('/login', ['username' => '0500000001', 'password' => 'secret-pass']);

        session(['otp_expires_at' => time() - 1]);

        $digits = str_split($this->sms->code);
        $payload = [];
        foreach ($digits as $i => $digit) {
            $payload['n'.($i + 1)] = $digit;
        }

        $this->post('/otp', $payload)->assertSessionHasErrors('otp');
        $this->assertFalse(session()->has('hr_user_id'));
    }

    public function test_otp_cannot_be_reused(): void
    {
        $this->post('/login', ['username' => '0500000001', 'password' => 'secret-pass']);

        $digits = str_split($this->sms->code);
        $payload = [];
        foreach ($digits as $i => $digit) {
            $payload['n'.($i + 1)] = $digit;
        }

        $this->post('/otp', $payload)->assertOk();
        $this->assertTrue(session()->has('hr_user_id'));

        // Replaying the same code must fail: the pending OTP session state
        // (step1/code/...) was cleared on first success, so the otp.pending
        // middleware now bounces the request away before it can be re-verified.
        $this->post('/otp', $payload)->assertRedirect();
        $this->assertFalse(session()->has('step1'));
    }

    public function test_unknown_identifier_gives_generic_response_for_both_mobile_and_email(): void
    {
        $unknownMobile = $this->post('/login', ['username' => '0599999999', 'password' => 'whatever']);
        $unknownEmail = $this->post('/login', ['username' => 'nobody@example.test', 'password' => 'whatever']);

        $unknownMobile->assertSessionHasErrors('username');
        $unknownEmail->assertSessionHasErrors('username');
        $this->assertSame(
            session('errors')->first('username'),
            __('login.errors.invalid_credentials')
        );
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $this->post('/login', ['username' => 'LOGIN_TEST_INACTIVE', 'password' => 'secret-pass'])
            ->assertSessionHasErrors('username');

        $this->assertFalse(session()->has('step1'));
    }

    public function test_resend_cooldown_is_enforced(): void
    {
        config(['hm.otp.resend_cooldown_seconds' => 60]);

        $this->post('/login', ['username' => '0500000001', 'password' => 'secret-pass']);
        $firstCode = $this->sms->code;

        $this->post('/otp/resend')->assertSessionHasErrors('otp');
        $this->assertSame($firstCode, $this->sms->code);

        session(['code_time' => time() - 61]);
        $this->post('/otp/resend')->assertSessionDoesntHaveErrors('otp');
    }
}

class LoginOtpSmsFake extends SmsGateway
{
    public array $messages = [];

    public string $code = '';

    public function send(string $mobile, string $message, ?string $sender = null): array
    {
        preg_match('/(\d{6})/', $message, $matches);
        $this->code = (string) ($matches[1] ?? '');
        $this->messages[] = compact('mobile', 'message');

        return ['ok' => true, 'provider' => 'fake', 'response' => null, 'error' => null];
    }
}
