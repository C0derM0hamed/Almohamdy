<?php

namespace App\Console\Commands;

use App\Services\Licenses\LicenseExpiryAlertService;
use Illuminate\Console\Command;

class SendLicenseExpiryAlerts extends Command
{
    protected $signature = 'hm:licenses-send-expiry-alerts {--dry-run : Report matching records without changing data or sending notifications}';

    protected $description = 'Update license expiry states, send deduplicated alerts, and escalate overdue undertakings';

    public function handle(LicenseExpiryAlertService $service): int
    {
        $summary = $service->run((bool) $this->option('dry-run'));
        $this->table(['Checked', 'Alerts', 'Statuses updated', 'Undertakings escalated', 'Dry run'], [[
            $summary['checked'], $summary['alerts'], $summary['statuses_updated'], $summary['undertakings_escalated'], $summary['dry_run'] ? 'yes' : 'no',
        ]]);

        return self::SUCCESS;
    }
}
