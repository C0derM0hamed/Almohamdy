<?php

namespace App\Console\Commands;

use App\Mail\DepartmentReplyLinkMail;
use App\Services\Sms\SmsGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestCorporateCommunicationNotification extends Command
{
    protected $signature = 'hm:cc-test-notification
        {--email= : Destination email address}
        {--mobile= : Destination mobile number}
        {--mail : Force send a test email}
        {--sms : Force send a test SMS}';

    protected $description = 'Smoke-test Corporate Communication email/SMS notification channels';

    public function handle(SmsGateway $sms): int
    {
        $email = trim((string) $this->option('email'));
        $mobile = trim((string) $this->option('mobile'));
        $doMail = (bool) $this->option('mail') || $email !== '';
        $doSms = (bool) $this->option('sms') || $mobile !== '';

        if (! $doMail && ! $doSms) {
            $this->error('Provide --email and/or --mobile (or --mail / --sms with those values).');

            return self::FAILURE;
        }

        $replyUrl = url('/public/government-circulars/formal/test-token');
        $ok = true;

        $this->line('Notifications master switch: '.(config('hm.cc_notifications.enabled') ? 'ON' : 'OFF'));
        $this->line('Mail channel config: '.(config('hm.cc_notifications.mail') ? 'ON' : 'OFF'));
        $this->line('SMS channel config: '.(config('hm.cc_notifications.sms') ? 'ON' : 'OFF'));
        $this->line('SMS provider: '.$sms->provider().' (configured: '.($sms->isConfigured() ? 'yes' : 'no').')');
        $this->line('APP_URL: '.config('app.url'));
        $this->newLine();

        if ($doMail) {
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error('Valid --email is required for mail test.');
                $ok = false;
            } else {
                try {
                    Mail::to($email)->send(new DepartmentReplyLinkMail(
                        recipientName: 'Test Recipient',
                        subjectLine: 'CC notification smoke test',
                        intro: 'This is a Corporate Communication notification smoke test from HMS.',
                        replyUrl: $replyUrl,
                    ));
                    $this->info("Mail sent to {$email}");
                } catch (Throwable $e) {
                    $ok = false;
                    $this->error('Mail failed: '.$e->getMessage());
                }
            }
        }

        if ($doSms) {
            if ($mobile === '') {
                $this->error('--mobile is required for SMS test.');
                $ok = false;
            } else {
                $message = __('cc_notifications.sms_body', [
                    'name' => 'Test Recipient',
                    'intro' => 'CC SMS smoke test',
                    'url' => $replyUrl,
                ]);
                $result = $sms->send($mobile, $message);
                if ($result['ok']) {
                    $this->info("SMS accepted by provider [{$result['provider']}] for {$mobile}");
                    if ($result['response']) {
                        $this->line('Response: '.$result['response']);
                    }
                } else {
                    $ok = false;
                    $this->error('SMS failed ['.$result['provider'].']: '.($result['error'] ?: 'unknown'));
                }
            }
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
