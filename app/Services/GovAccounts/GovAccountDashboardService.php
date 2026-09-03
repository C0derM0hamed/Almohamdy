<?php

namespace App\Services\GovAccounts;

use App\Models\GovAccountNotice;
use App\Models\GovAccountNoticeRecipient;
use App\Repositories\GovAccounts\GovAccountRepository;
use App\Support\GovAccounts\GovAccountPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GovAccountDashboardService
{
    public function __construct(private readonly GovAccountRepository $repository) {}

    public function metrics(): array
    {
        $this->repository->authorizeAny(GovAccountPermissions::VIEW, GovAccountPermissions::PROCESS);
        $accounts = $this->repository->scopedAccounts();
        $requests = $this->repository->scopedRequests();
        $noticeIds = GovAccountNotice::query()->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->whereNotNull('sent_at')->select('id');
        $recipientBase = GovAccountNoticeRecipient::query()->whereIn('notice_id', $noticeIds);
        $recipientCount = (clone $recipientBase)->count();
        $viewedCount = (clone $recipientBase)->whereNotNull('viewed_at')->count();

        return [
            'accounts' => ['total' => (clone $accounts)->count()] + $this->counts($accounts, 'status'),
            'requests' => ['total' => (clone $requests)->count()],
            'requests_by_status' => $this->counts($requests, 'status'),
            'requests_by_type' => $this->counts($requests, 'type'),
            'multi_account_employees' => DB::query()->fromSub(
                (clone $accounts)->select('employee_user_id')->groupBy('employee_user_id')->havingRaw('COUNT(*) > 1'),
                'multi_account_employees',
            )->count(),
            'notices' => [
                'sent' => GovAccountNotice::query()->where('companies_groups_id', (int) session('companies_groups_id', 0))->whereNotNull('sent_at')->count(),
                'recipients' => $recipientCount,
                'viewed' => $viewedCount,
                'view_rate' => $recipientCount === 0 ? 0.0 : round(($viewedCount / $recipientCount) * 100, 1),
            ],
        ];
    }

    /** @return array<string,int> */
    private function counts(Builder $query, string $column): array
    {
        return (clone $query)->select($column, DB::raw('COUNT(*) AS total'))->groupBy($column)
            ->pluck('total', $column)->map(fn ($count): int => (int) $count)->all();
    }
}
