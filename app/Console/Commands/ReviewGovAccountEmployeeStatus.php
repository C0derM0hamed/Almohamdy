<?php

namespace App\Console\Commands;

use App\Services\GovAccounts\GovAccountEmployeeStatusReviewService;
use Illuminate\Console\Command;

class ReviewGovAccountEmployeeStatus extends Command
{
    protected $signature = 'hm:gov-accounts-review-employee-status';

    protected $description = 'Flag official accounts whose employees are reported inactive by the configured HR provider';

    public function handle(GovAccountEmployeeStatusReviewService $review): int
    {
        $summary = $review->run();
        if (! $summary['enabled']) {
            $this->info('Government Accounts employee-status review is disabled.');

            return self::SUCCESS;
        }
        $this->table(['Checked', 'Flagged', 'Notifications', 'Accounts changed'], [[$summary['checked'], $summary['flagged'], $summary['notifications'], 0]]);

        return self::SUCCESS;
    }
}
