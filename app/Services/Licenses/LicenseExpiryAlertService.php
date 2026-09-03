<?php

namespace App\Services\Licenses;

use App\Models\License;
use App\Models\LicenseNotification;
use App\Models\LicenseStatus;
use App\Models\LicenseUndertaking;
use App\Support\Licenses\BusinessDayCalculator;
use App\Support\Licenses\LicenseAlertWindow;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class LicenseExpiryAlertService
{
    public function __construct(
        private readonly LicenseAlertWindow $windows,
        private readonly BusinessDayCalculator $businessDays,
        private readonly LicenseNotificationService $notifications,
    ) {}

    /** @return array{checked:int,alerts:int,statuses_updated:int,undertakings_escalated:int,dry_run:bool} */
    public function run(bool $dryRun = false, ?CarbonImmutable $at = null): array
    {
        $at ??= CarbonImmutable::now();
        $summary = ['checked' => 0, 'alerts' => 0, 'statuses_updated' => 0, 'undertakings_escalated' => 0, 'dry_run' => $dryRun];

        License::query()->where('publish', true)->with(['responsibleUser', 'status'])->orderBy('id')
            ->chunkById(100, function ($licenses) use (&$summary, $dryRun, $at): void {
                foreach ($licenses as $license) {
                    $summary['checked']++;
                    $window = $this->windows->for($license->expiry_date, $at);
                    $eventType = $this->windows->eventType($window);
                    if ($eventType === null) {
                        continue;
                    }

                    $expiry = $this->dateString($license->expiry_date);
                    if ($this->alreadyDelivered($license, $eventType, $expiry, $at)) {
                        continue;
                    }

                    if ($dryRun) {
                        $summary['alerts']++;

                        continue;
                    }

                    DB::transaction(function () use ($license, $window, $expiry, $at, &$summary): void {
                        $targetStatus = match ($window) {
                            LicenseAlertWindow::EXPIRED => 'expired',
                            LicenseAlertWindow::YELLOW, LicenseAlertWindow::SIXTY_DAYS, LicenseAlertWindow::RED => 'near_expiry',
                            default => null,
                        };
                        if ($targetStatus !== null
                            && ($window === LicenseAlertWindow::EXPIRED || (string) $license->status?->code === 'active')) {
                            $statusId = (int) LicenseStatus::query()->where('code', $targetStatus)->value('id');
                            if ($statusId > 0 && (int) $license->status_id !== $statusId) {
                                $license->update(['status_id' => $statusId]);
                                $summary['statuses_updated']++;
                            }
                        }

                        $days = $this->windows->daysRemaining($license->expiry_date, $at);
                        $notice = __('licenses.notifications.expiry_body', ['days' => max(0, $days), 'date' => $expiry]);
                        DB::table('license_timeline')->insert([
                            'license_id' => $license->getKey(), 'event_type' => $this->timelineEvent($window),
                            'status_id' => $license->status_id, 'notice' => $notice,
                            'meta' => json_encode(['expiry_date' => $expiry, 'days_remaining' => $days], JSON_UNESCAPED_UNICODE),
                            'created_by' => null, 'created_by_type' => 'scheduler', 'branch_id' => null, 'date' => now(),
                        ]);
                    });

                    $subject = __('licenses.notifications.expiry_subject');
                    $recipients = collect();
                    if ($license->responsibleUser !== null) {
                        $recipients->push($license->responsibleUser);
                    }
                    $recipients = $recipients->merge($this->notifications->permissionRecipients((int) $license->companies_groups_id, 'licenses_admin'));

                    if (in_array($window, [LicenseAlertWindow::RED, LicenseAlertWindow::EXPIRED], true)) {
                        $recipients = $recipients->merge($this->notifications->escalationRecipients((int) $license->companies_groups_id));
                        $subject = __('licenses.notifications.escalation_subject');
                    }
                    $this->notifications->notifyUsers($license, $recipients, $eventType, $subject, __('licenses.notifications.expiry_body', [
                        'days' => max(0, $this->windows->daysRemaining($license->expiry_date, $at)), 'date' => $expiry,
                    ]), null, ['expiry_date' => $expiry, 'window' => $window]);
                    $summary['alerts']++;
                }
            });

        $summary['undertakings_escalated'] = $this->escalateUndertakings($dryRun, $at);

        return $summary;
    }

    private function escalateUndertakings(bool $dryRun, CarbonImmutable $at): int
    {
        $days = (int) config('hm.licenses.undertaking_escalation_days', 3);
        $count = 0;
        LicenseUndertaking::query()->where('status', 'pending')->with(['license.responsibleUser'])->chunkById(100,
            function ($undertakings) use ($days, $dryRun, $at, &$count): void {
                foreach ($undertakings as $undertaking) {
                    if (! $this->businessDays->isOverdue($undertaking->requested_at, $days, $at) || $undertaking->license === null) {
                        continue;
                    }
                    $count++;
                    if ($dryRun) {
                        continue;
                    }
                    $undertaking->update(['status' => 'escalated', 'escalated_at' => now()]);
                    DB::table('license_timeline')->insert([
                        'license_id' => $undertaking->license_id, 'event_type' => 'undertaking_escalated',
                        'status_id' => $undertaking->license->status_id, 'notice' => __('licenses.timeline.undertaking_escalated'),
                        'meta' => json_encode(['undertaking_id' => $undertaking->getKey(), 'responsible_user_id' => $undertaking->user_id], JSON_UNESCAPED_UNICODE),
                        'created_by' => null, 'created_by_type' => 'scheduler', 'branch_id' => null, 'date' => now(),
                    ]);
                    $recipients = $this->notifications->undertakingEscalationRecipients((int) $undertaking->license->companies_groups_id);
                    $this->notifications->notifyUsers($undertaking->license, $recipients, 'undertaking_overdue', __('licenses.notifications.undertaking_overdue_subject'), __('licenses.notifications.undertaking_overdue_body'), null, [
                        'undertaking_id' => $undertaking->getKey(), 'responsible_user_id' => $undertaking->user_id,
                    ]);
                }
            });

        return $count;
    }

    private function alreadyDelivered(License $license, string $eventType, string $expiry, CarbonImmutable $at): bool
    {
        $query = LicenseNotification::query()->where('license_id', $license->getKey())->where('event_type', $eventType)
            ->where('channel', 'inapp')->where('meta->expiry_date', $expiry);
        if ($eventType !== 'license_expired') {
            return $query->exists();
        }

        $last = $query->latest('created_at')->value('created_at');
        if ($last === null) {
            return false;
        }

        return CarbonImmutable::parse($last)->addDays((int) config('hm.licenses.expired_reescalate_days', 7))->greaterThan($at);
    }

    private function timelineEvent(string $window): string
    {
        return match ($window) {
            LicenseAlertWindow::YELLOW => 'yellow',
            LicenseAlertWindow::SIXTY_DAYS => 'reminder_sent',
            LicenseAlertWindow::RED => 'red',
            default => 'expired',
        };
    }

    private function dateString(mixed $date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : CarbonImmutable::parse((string) $date)->toDateString();
    }
}
