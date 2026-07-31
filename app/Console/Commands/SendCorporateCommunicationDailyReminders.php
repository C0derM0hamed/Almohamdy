<?php

namespace App\Console\Commands;

use App\Services\CorporateCommunications\DailyReminderService;
use Illuminate\Console\Command;

class SendCorporateCommunicationDailyReminders extends Command
{
    protected $signature = 'hm:cc-send-daily-reminders
        {--dry-run : List pending records without sending notifications}';

    protected $description = 'Send daily 7:00 AM reminders for pending inspection visit replies and data requests';

    public function handle(DailyReminderService $reminders): int
    {
        if (! (bool) config('hm.cc_notifications.reminders', true)) {
            $this->warn('Corporate Communication reminders are disabled (HM_CC_REMINDERS_ENABLED).');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $visits = $reminders->pendingInspectionVisits();
            $requests = $reminders->pendingDataRequests();

            $this->info('Dry run — no notifications sent.');
            $this->line('Pending inspection visits: '.$visits->count());
            foreach ($visits as $visit) {
                $this->line(sprintf(
                    '  visit #%d status=%s users=%s deadline=%s',
                    (int) $visit->id,
                    (string) $visit->status,
                    (string) $visit->users,
                    optional($visit->reply_time)->format('Y-m-d H:i') ?: '—'
                ));
            }

            $this->line('Pending data requests: '.$requests->count());
            foreach ($requests as $request) {
                $this->line(sprintf(
                    '  request #%d status=%s section=%s reminder=%s',
                    (int) $request->id,
                    (string) $request->status,
                    (string) $request->send_Section,
                    $request->reminderAt() ?: '—'
                ));
            }

            return self::SUCCESS;
        }

        $stats = $reminders->sendPendingReminders();

        $this->info(sprintf(
            'Reminders processed: %d inspection visit(s), %d data request(s), %d notification(s).',
            $stats['inspection_visits'],
            $stats['data_requests'],
            $stats['notifications']
        ));

        return self::SUCCESS;
    }
}
