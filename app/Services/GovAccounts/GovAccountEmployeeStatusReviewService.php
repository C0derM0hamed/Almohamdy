<?php

namespace App\Services\GovAccounts;

use App\Models\GovAccount;

class GovAccountEmployeeStatusReviewService
{
    public function __construct(
        private readonly EmployeeStatusProviderInterface $provider,
        private readonly GovAccountNotificationService $notifications,
    ) {}

    /** @return array{checked:int,flagged:int,notifications:int,enabled:bool} */
    public function run(): array
    {
        if (! config('hm.gov_accounts.employee_status.enabled', false)) {
            return ['checked' => 0, 'flagged' => 0, 'notifications' => 0, 'enabled' => false];
        }

        $summary = ['checked' => 0, 'flagged' => 0, 'notifications' => 0, 'enabled' => true];
        GovAccount::query()->whereNot('status', 'closed')->with('employee')->orderBy('id')->chunkById(100, function ($accounts) use (&$summary): void {
            foreach ($accounts as $account) {
                if ($account->employee === null) {
                    continue;
                }
                $summary['checked']++;
                if ($this->provider->isActive($account->employee) !== false) {
                    continue;
                }
                $summary['flagged']++;
                $recipients = $this->notifications->processors((int) $account->companies_groups_id)
                    ->merge($this->notifications->hrUsers((int) $account->companies_groups_id))->unique('hr_id');
                foreach ($recipients as $recipient) {
                    if ($this->notifications->notifyEmployeeStatusActionRequired($account, $recipient)) {
                        $summary['notifications']++;
                    }
                }
            }
        });

        return $summary;
    }
}
