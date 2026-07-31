<?php

namespace App\Services\CorporateCommunications;

use App\Mail\DepartmentReplyLinkMail;
use App\Models\GovernmentCircularSectionAdministrator;
use App\Services\Sms\SmsGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends department reply / formal circular links by email and SMS.
 */
class DepartmentNotificationService
{
    public function __construct(
        private readonly SmsGateway $sms,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('hm.cc_notifications.enabled', false);
    }

    public function mailEnabled(): bool
    {
        return $this->isEnabled() && (bool) config('hm.cc_notifications.mail', true);
    }

    public function smsEnabled(): bool
    {
        return $this->isEnabled() && (bool) config('hm.cc_notifications.sms', false);
    }

    public function notifyAdministrator(
        GovernmentCircularSectionAdministrator $admin,
        string $subject,
        string $intro,
        string $replyUrl,
        string $moduleKey,
    ): void {
        $email = trim((string) ($admin->email ?? ''));
        $mobile = trim((string) ($admin->mobile ?? ''));
        $name = (string) ($admin->administrator ?: ($email !== '' ? $email : 'Administrator'));

        Log::info('cc.department_notification', [
            'module' => $moduleKey,
            'administrator_id' => (int) $admin->id,
            'email' => $email !== '' ? $email : null,
            'mobile' => $mobile !== '' ? $mobile : null,
            'reply_url' => $replyUrl,
            'mail_enabled' => $this->mailEnabled(),
            'sms_enabled' => $this->smsEnabled(),
            'sms_provider' => $this->sms->provider(),
        ]);

        if ($this->mailEnabled() && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendMail($email, $name, $subject, $intro, $replyUrl, $moduleKey);
        }

        if ($this->smsEnabled() && $mobile !== '') {
            $this->sendSms($mobile, $name, $intro, $replyUrl, $moduleKey);
        }
    }

    private function sendMail(
        string $email,
        string $name,
        string $subject,
        string $intro,
        string $replyUrl,
        string $moduleKey,
    ): void {
        try {
            Mail::to($email)->send(new DepartmentReplyLinkMail(
                recipientName: $name,
                subjectLine: $subject,
                intro: $intro,
                replyUrl: $replyUrl,
            ));

            Log::info('cc.department_notification.mail_sent', [
                'module' => $moduleKey,
                'email' => $email,
            ]);
        } catch (Throwable $e) {
            Log::error('cc.department_notification.mail_failed', [
                'module' => $moduleKey,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendSms(
        string $mobile,
        string $name,
        string $intro,
        string $replyUrl,
        string $moduleKey,
    ): void {
        $message = __('cc_notifications.sms_body', [
            'name' => $name,
            'intro' => $intro,
            'url' => $replyUrl,
        ]);

        $result = $this->sms->send($mobile, $message);

        Log::info('cc.department_notification.sms_result', [
            'module' => $moduleKey,
            'mobile' => $mobile,
            'ok' => $result['ok'],
            'provider' => $result['provider'],
            'error' => $result['error'],
        ]);
    }
}
