<?php

namespace App\Services\Licenses;

use App\Mail\LicenseNotificationMail;
use App\Models\License;
use App\Models\LicenseNotification;
use App\Models\LicensePaymentRequest;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use App\Services\Auth\PermissionService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LicenseNotificationService
{
    public function __construct(
        private readonly SmsGateway $sms,
        private readonly PermissionService $permissions,
    ) {}

    /**
     * Persist an in-app notification and attempt enabled external channels.
     * Every attempted/skipped channel is recorded; transport failure never rolls back business work.
     *
     * @param  array<string,mixed>  $meta
     */
    public function notifyUser(
        License $license,
        User|int $recipient,
        string $eventType,
        string $subject,
        string $message,
        ?LicensePaymentRequest $paymentRequest = null,
        array $meta = [],
        ?string $reason = null,
    ): void {
        $user = $recipient instanceof User ? $recipient : User::query()->find($recipient);
        if ($user === null) {
            Log::warning('licenses.notification.recipient_missing', ['license_id' => $license->getKey(), 'recipient' => $recipient]);

            return;
        }

        $email = trim((string) $user->hr_email_address);
        $mobile = trim((string) $user->mobile);
        $expiryDate = $license->expiry_date instanceof \DateTimeInterface
            ? $license->expiry_date->format('Y-m-d')
            : CarbonImmutable::parse((string) $license->expiry_date)->toDateString();
        $meta += ['expiry_date' => $expiryDate];
        $base = [
            'license_id' => (int) $license->getKey(),
            'payment_request_id' => $paymentRequest?->getKey(),
            'event_type' => $eventType,
            'recipient_user_id' => (int) $user->getKey(),
            'recipient_email' => $email !== '' ? $email : null,
            'recipient_mobile' => $mobile !== '' ? $mobile : null,
            'reason' => $reason ?: $message,
            'meta' => $meta,
            'created_at' => now(),
        ];

        LicenseNotification::query()->create($base + ['channel' => 'inapp', 'status' => 'logged']);

        $url = $this->licenseUrl($license);
        $this->deliverMail($base, $user->displayName(), $subject, $message, $url, $email);
        $this->deliverSms($base, $message, $url, $mobile);
    }

    /** @param iterable<User> $users @param array<string,mixed> $meta */
    public function notifyUsers(
        License $license,
        iterable $users,
        string $eventType,
        string $subject,
        string $message,
        ?LicensePaymentRequest $paymentRequest = null,
        array $meta = [],
        ?string $reason = null,
    ): void {
        foreach (collect($users)->unique(fn (User $user) => $user->getKey()) as $user) {
            $this->notifyUser($license, $user, $eventType, $subject, $message, $paymentRequest, $meta, $reason);
        }
    }

    /** @return Collection<int,User> */
    public function escalationRecipients(int $companyId): Collection
    {
        return User::query()
            ->where('ra_users.companies_groups_id', $companyId)
            ->whereIn('ra_users.hr_id', function ($query) use ($companyId): void {
                $query->select('license_escalation_group_members.user_id')
                    ->from('license_escalation_group_members')
                    ->join('license_escalation_groups', 'license_escalation_groups.id', '=', 'license_escalation_group_members.group_id')
                    ->where('license_escalation_groups.companies_groups_id', $companyId)
                    ->where('license_escalation_groups.publish', true);
            })->get();
    }

    /** @return Collection<int,User> */
    public function financeRecipients(int $companyId): Collection
    {
        return $this->permissionRecipients($companyId, 'licenses_finance');
    }

    /** @return Collection<int,User> */
    public function permissionRecipients(int $companyId, string $permissionCode): Collection
    {
        $users = User::query()->where('companies_groups_id', $companyId)->activated()->get();
        if (! Schema::hasTable('user_permission') && ! Schema::hasTable('user_groups_permission')) {
            return collect();
        }

        return $users->filter(function (User $user) use ($permissionCode): bool {
            $direct = Schema::hasTable('user_permission')
                ? DB::table('user_permission')->where('userid', $user->getKey())->where('page', $permissionCode)->get(['permit'])
                : collect();
            $rows = $direct->isNotEmpty() || ! Schema::hasTable('user_groups_permission')
                ? $direct
                : DB::table('user_groups_permission')->where('groupid', (int) $user->groupid)->where('page', $permissionCode)->get(['permit']);

            return ! $rows->contains(fn ($row) => (string) $row->permit === '1')
                && $rows->contains(fn ($row) => (string) $row->permit === '2');
        })->values();
    }

    /** @return Collection<int,User> */
    public function undertakingEscalationRecipients(int $companyId): Collection
    {
        return $this->permissionRecipients($companyId, 'licenses_admin')
            ->merge(User::query()->where('companies_groups_id', $companyId)->where('hr_user_level', 3)->activated()->get())
            ->unique(fn (User $user) => $user->getKey())->values();
    }

    public function unreadCountForCurrentUser(): int
    {
        return $this->inboxQuery()->whereNull('read_at')->count();
    }

    public function inboxForCurrentUser(int $perPage = 25): LengthAwarePaginator
    {
        return $this->inboxQuery()->with('license')->latest('created_at')->paginate($perPage)->withQueryString();
    }

    public function markReadForCurrentUser(int $notificationId): LicenseNotification
    {
        $notification = $this->inboxQuery()->whereKey($notificationId)->firstOrFail();
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return $notification;
    }

    /** @param array<string,mixed> $base */
    private function deliverMail(array $base, string $name, string $subject, string $message, string $url, string $email): void
    {
        if (! (bool) config('hm.licenses.notifications.enabled', true)
            || ! (bool) config('hm.licenses.notifications.mail', true)
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            LicenseNotification::query()->create($base + [
                'channel' => 'mail', 'status' => 'failed', 'error' => $email === '' ? 'Missing recipient email' : 'Mail channel disabled or invalid email',
            ]);

            return;
        }

        try {
            Mail::to($email)->send(new LicenseNotificationMail($name, $subject, $message, $url));
            LicenseNotification::query()->create($base + ['channel' => 'mail', 'status' => 'sent']);
        } catch (Throwable $exception) {
            LicenseNotification::query()->create($base + ['channel' => 'mail', 'status' => 'failed', 'error' => $exception->getMessage()]);
            Log::error('licenses.notification.mail_failed', ['email' => $email, 'error' => $exception->getMessage()]);
        }
    }

    /** @param array<string,mixed> $base */
    private function deliverSms(array $base, string $message, string $url, string $mobile): void
    {
        if (! (bool) config('hm.licenses.notifications.enabled', true)
            || ! (bool) config('hm.licenses.notifications.sms', false)
            || $mobile === '' || ! $this->sms->isConfigured()) {
            LicenseNotification::query()->create($base + [
                'channel' => 'sms', 'status' => 'failed', 'error' => $mobile === '' ? 'Missing recipient mobile' : 'SMS channel/provider disabled',
            ]);

            return;
        }

        $result = $this->sms->send($mobile, $message.' '.$url);
        LicenseNotification::query()->create($base + [
            'channel' => 'sms',
            'status' => $result['ok'] ? 'sent' : 'failed',
            'error' => $result['error'],
        ]);
    }

    private function licenseUrl(License $license): string
    {
        try {
            return route('modules.licenses.show', $license);
        } catch (Throwable) {
            return url('/modules/licenses/'.$license->getKey());
        }
    }

    private function inboxQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return LicenseNotification::query()
            ->where('channel', LicenseNotification::CHANNEL_IN_APP)
            ->where('recipient_user_id', (int) session('hr_user_id', 0))
            ->whereHas('license', function ($query): void {
                $query->where('companies_groups_id', (int) session('companies_groups_id', 0))
                    ->where('publish', true);
                $branchId = (int) session('hr_branch_id', 0);
                if (! $this->permissions->isAdmin() && $branchId > 0) {
                    $query->whereHas('departments', fn ($departments) => $departments->where('branches.id', $branchId));
                }
            });
    }
}
