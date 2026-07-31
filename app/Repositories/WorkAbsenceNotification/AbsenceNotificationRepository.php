<?php

namespace App\Repositories\WorkAbsenceNotification;

use App\Models\AbsenceNotificationService;
use App\Models\AbsenceNotificationServiceActionType;
use App\Models\AbsenceNotificationServiceDeathLeaveCategory;
use App\Models\AbsenceNotificationServiceType;
use App\Models\AbsenceNotificationServiceSendTo;
use App\Models\User;
use App\Services\WorkAbsenceNotification\AbsenceNotificationWorkflowResolver;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AbsenceNotificationRepository
{
    /**
     * @var list<string>
     */
    private const LIST_COLUMNS = [
        'id',
        'branch_id',
        'companies_groups_id',
        'user_id',
        'memo_types_id',
        'date',
        'begin_date',
        'end_date',
        'absence_days',
        'sick_leave_file',
        'relationship',
        'deceased_relationship',
        'medical_authority',
        'absence_reason',
        'action_type',
        'action_date',
        'action_by',
        'activated_by',
        'activated_at',
    ];

    /**
     * @var list<string>
     */
    private const DETAIL_COLUMNS = [
        'id',
        'branch_id',
        'companies_groups_id',
        'user_id',
        'memo_types_id',
        'date',
        'sms_tocken',
        'activated_by',
        'activated_at',
        'begin_date',
        'end_date',
        'sick_leave_file',
        'relationship',
        'deceased_relationship',
        'medical_authority',
        'absence_days',
        'absence_reason',
        'action_type',
        'action_date',
        'action_by',
    ];

    /**
     * @var list<string>
     */
    private const PROCESSING_COLUMNS = [
        'id',
        'companies_groups_id',
        'action_type',
        'activated_by',
        'activated_at',
    ];

    /**
     * @var list<string>
     */
    private const MEMO_COLUMNS = [
        'id',
        'absence_notification_service_id',
        'user_id',
        'memo_type',
        'date',
        'begin_date',
        'end_date',
        'hours',
        'pending_inquiries',
    ];

    /**
     * @var list<string>
     */
    private const MEMO_RECIPIENT_COLUMNS = [
        'id',
        'memo_id',
        'user_id',
        'seen_at',
    ];

    public function __construct(
        private readonly AbsenceNotificationWorkflowResolver $workflowResolver,
    ) {}

    public function scopedQuery(): Builder
    {
        $query = AbsenceNotificationService::query()->select(self::LIST_COLUMNS);

        $companyGroupId = (int) session('companies_groups_id', 0);

        if ($companyGroupId > 0) {
            $query->where('companies_groups_id', $companyGroupId);
        }
        if ((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0) {
            $query->where('branch_id', (int) session('hr_branch_id'));
        }

        return $query;
    }

    /**
     * @return LengthAwarePaginator<int, AbsenceNotificationService>
     */
    public function paginateFiltered(
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?int $notificationTypeId,
        string $employeeSearch,
        ?string $workflowStatus,
        int $perPage,
    ): LengthAwarePaginator {
        return $this->filteredQuery(
            $dateFrom,
            $dateTo,
            $notificationTypeId,
            $employeeSearch,
            $workflowStatus,
        )
            ->with([
                'employee:hr_id,hr_first_name,hr_last_name,hr_username',
                'notificationType:id,name_en,name_ar',
                'actionType:id,name_en,name_ar',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Builder<int, AbsenceNotificationService>
     */
    public function filteredQuery(
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?int $notificationTypeId,
        string $employeeSearch,
        ?string $workflowStatus,
    ): Builder {
        return $this->applyListFilters(
            $this->scopedQuery(),
            $dateFrom,
            $dateTo,
            $notificationTypeId,
            $employeeSearch,
            $workflowStatus,
        )->orderByDesc('id');
    }

    /**
     * @param Builder<int, AbsenceNotificationService> $query
     * @return Builder<int, AbsenceNotificationService>
     */
    private function applyListFilters(
        Builder $query,
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?int $notificationTypeId,
        string $employeeSearch,
        ?string $workflowStatus,
    ): Builder {
        return $query
            ->when($dateFrom !== null, function (Builder $builder) use ($dateFrom) {
                $builder->whereRaw('CAST(`date` AS UNSIGNED) >= ?', [$dateFrom->startOfDay()->timestamp]);
            })
            ->when($dateTo !== null, function (Builder $builder) use ($dateTo) {
                $builder->whereRaw('CAST(`date` AS UNSIGNED) <= ?', [$dateTo->endOfDay()->timestamp]);
            })
            ->when($notificationTypeId !== null, fn (Builder $builder) => $builder->where('memo_types_id', $notificationTypeId))
            ->when($employeeSearch !== '', function (Builder $builder) use ($employeeSearch) {
                $builder->where(function (Builder $inner) use ($employeeSearch) {
                    $inner->where('user_id', 'like', '%'.$employeeSearch.'%')
                        ->orWhereHas('employee', fn (Builder $employee) => $employee
                            ->where('hr_first_name', 'like', '%'.$employeeSearch.'%')
                            ->orWhere('hr_last_name', 'like', '%'.$employeeSearch.'%')
                            ->orWhere('hr_username', 'like', '%'.$employeeSearch.'%'));
                });
            })
            ->when($workflowStatus !== null && $workflowStatus !== '', function (Builder $builder) use ($workflowStatus) {
                $this->applyWorkflowStatusFilter($builder, $workflowStatus);
            });
    }

    public function applyWorkflowStatusFilter(Builder $query, string $status): Builder
    {
        return $this->workflowResolver->applyWorkflowStatusFilter($query, $status);
    }

    /**
     * @return array{total: int, pending: int, action_taken: int, activated: int, this_month: int}
     */
    public function dashboardSummaryCounts(): array
    {
        $monthStart = Carbon::now()->startOfMonth()->timestamp;
        $monthEnd = Carbon::now()->endOfMonth()->timestamp;

        $baseQuery = $this->reportingBaseQuery();

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => $this->workflowResolver
                ->applyWorkflowStatusFilter(clone $baseQuery, AbsenceNotificationWorkflowResolver::PENDING)
                ->count(),
            'action_taken' => $this->workflowResolver
                ->applyWorkflowStatusFilter(clone $baseQuery, AbsenceNotificationWorkflowResolver::ACTION_TAKEN)
                ->count(),
            'activated' => $this->workflowResolver
                ->applyWorkflowStatusFilter(clone $baseQuery, AbsenceNotificationWorkflowResolver::ACTIVATED)
                ->count(),
            'this_month' => (clone $baseQuery)
                ->whereRaw('CAST(`date` AS UNSIGNED) >= ?', [$monthStart])
                ->whereRaw('CAST(`date` AS UNSIGNED) <= ?', [$monthEnd])
                ->count(),
        ];
    }

    /**
     * @return Collection<int, object{memo_types_id: int, name_en: ?string, name_ar: ?string, total: int}>
     */
    public function pendingByNotificationType(): Collection
    {
        return $this->countByNotificationType(AbsenceNotificationWorkflowResolver::PENDING);
    }

    /**
     * @return Collection<int, object{memo_types_id: int, name_en: ?string, name_ar: ?string, total: int}>
     */
    public function actionTakenByNotificationType(): Collection
    {
        return $this->countByNotificationType(AbsenceNotificationWorkflowResolver::ACTION_TAKEN);
    }

    /**
     * @return Collection<int, object{memo_types_id: int, name_en: ?string, name_ar: ?string, total: int}>
     */
    public function activatedByNotificationType(): Collection
    {
        return $this->countByNotificationType(AbsenceNotificationWorkflowResolver::ACTIVATED);
    }

    /**
     * @return Collection<int, object{report_date: string, total: int}>
     */
    public function last30DaysTrend(): Collection
    {
        $since = Carbon::now()->subDays(29)->startOfDay()->timestamp;

        return $this->reportingBaseQuery()
            ->selectRaw('DATE(FROM_UNIXTIME(CAST(`date` AS UNSIGNED))) as report_date')
            ->selectRaw('COUNT(*) as total')
            ->whereRaw('CAST(`date` AS UNSIGNED) >= ?', [$since])
            ->groupByRaw('DATE(FROM_UNIXTIME(CAST(`date` AS UNSIGNED)))')
            ->orderBy('report_date')
            ->get();
    }

    /**
     * @return Collection<int, object{absence_reason: string, total: int}>
     */
    public function topAbsenceReasons(int $limit = 10): Collection
    {
        return $this->reportingBaseQuery()
            ->select('absence_reason')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('absence_reason')
            ->where('absence_reason', '!=', '')
            ->groupBy('absence_reason')
            ->orderByDesc('total')
            ->orderBy('absence_reason')
            ->limit($limit)
            ->get();
    }

    private function reportingBaseQuery(): Builder
    {
        $companyGroupId = (int) session('companies_groups_id', 0);

        return AbsenceNotificationService::query()
            ->when($companyGroupId > 0, fn (Builder $query) => $query->where('companies_groups_id', $companyGroupId));
    }

    /**
     * @return Collection<int, object{memo_types_id: int, name_en: ?string, name_ar: ?string, total: int}>
     */
    private function countByNotificationType(string $status): Collection
    {
        $query = $this->reportingBaseQuery()
            ->leftJoin(
                'absence_notification_service_types as notification_types',
                'notification_types.id',
                '=',
                'absence_notification_service.memo_types_id',
            )
            ->select([
                'absence_notification_service.memo_types_id',
                'notification_types.name_en',
                'notification_types.name_ar',
            ])
            ->selectRaw('COUNT(*) as total');

        $this->workflowResolver->applyWorkflowStatusFilter($query, $status);

        return $query
            ->groupBy(
                'absence_notification_service.memo_types_id',
                'notification_types.name_en',
                'notification_types.name_ar',
            )
            ->orderByDesc('total')
            ->orderBy('absence_notification_service.memo_types_id')
            ->get();
    }

    public function findForDetail(int $id): ?AbsenceNotificationService
    {
        $companyGroupId = (int) session('companies_groups_id', 0);

        return AbsenceNotificationService::query()
            ->select(self::DETAIL_COLUMNS)
            ->when($companyGroupId > 0, fn (Builder $q) => $q->where('companies_groups_id', $companyGroupId))
            ->when((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0, fn (Builder $q) => $q->where('branch_id', (int) session('hr_branch_id')))
            ->whereKey($id)
            ->with([
                'employee:hr_id,hr_first_name,hr_last_name,hr_username,hr_email_address,mobile,job_title',
                'notificationType:id,name_en,name_ar',
                'actionType:id,name_en,name_ar',
                'actionByUser:hr_id,hr_first_name,hr_last_name,hr_username',
                'activatedByUser:hr_id,hr_first_name,hr_last_name,hr_username',
                'memos' => fn ($q) => $q->select(self::MEMO_COLUMNS)->orderBy('id'),
                'memos.employee:hr_id,hr_first_name,hr_last_name,hr_username',
                'memos.memoType:id,name_en,name_ar',
                'memos.recipients' => fn ($q) => $q->select(self::MEMO_RECIPIENT_COLUMNS)->orderBy('id'),
                'memos.recipients.recipient:hr_id,hr_first_name,hr_last_name,hr_username',
            ])
            ->first();
    }

    public function findForProcessing(int $id): ?AbsenceNotificationService
    {
        $companyGroupId = (int) session('companies_groups_id', 0);

        return AbsenceNotificationService::query()
            ->select(self::PROCESSING_COLUMNS)
            ->when($companyGroupId > 0, fn (Builder $q) => $q->where('companies_groups_id', $companyGroupId))
            ->when((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0, fn (Builder $q) => $q->where('branch_id', (int) session('hr_branch_id')))
            ->whereKey($id)
            ->first();
    }

    public function updateAction(int $id, int $actionTypeId, int $actionBy, string $actionDate): bool
    {
        $companyGroupId = (int) session('companies_groups_id', 0);

        return AbsenceNotificationService::query()
            ->when($companyGroupId > 0, fn (Builder $q) => $q->where('companies_groups_id', $companyGroupId))
            ->when((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0, fn (Builder $q) => $q->where('branch_id', (int) session('hr_branch_id')))
            ->whereKey($id)
            ->update([
                'action_type' => $actionTypeId,
                'action_by' => $actionBy,
                'action_date' => $actionDate,
            ]) > 0;
    }

    public function updateActivation(int $id, int $activatedBy, string $activatedAt): bool
    {
        $companyGroupId = (int) session('companies_groups_id', 0);

        return AbsenceNotificationService::query()
            ->when($companyGroupId > 0, fn (Builder $q) => $q->where('companies_groups_id', $companyGroupId))
            ->when((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0, fn (Builder $q) => $q->where('branch_id', (int) session('hr_branch_id')))
            ->whereKey($id)
            ->update([
                'activated_by' => $activatedBy,
                'activated_at' => $activatedAt,
            ]) > 0;
    }

    /**
     * @return Collection<int, AbsenceNotificationServiceActionType>
     */
    public function actionTypeOptions(): Collection
    {
        return AbsenceNotificationServiceActionType::query()
            ->select(['id', 'name_en', 'name_ar'])
            ->where('publish', 1)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, AbsenceNotificationServiceType>
     */
    public function notificationTypeOptions(): Collection
    {
        return AbsenceNotificationServiceType::query()
            ->select(['id', 'name_en', 'name_ar'])
            ->orderBy('ranking')
            ->orderBy('id')
            ->get();
    }

    public function paginateOwned(int $perPage = 15): LengthAwarePaginator
    {
        return AbsenceNotificationService::query()
            ->select(self::LIST_COLUMNS)
            ->where('user_id', (int) session('hr_user_id', 0))
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->with(['notificationType:id,name_en,name_ar', 'actionType:id,name_en,name_ar'])
            ->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findOwned(int $id): ?AbsenceNotificationService
    {
        return AbsenceNotificationService::query()->select(self::DETAIL_COLUMNS)
            ->whereKey($id)->where('user_id', (int) session('hr_user_id', 0))
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))->first();
    }

    public function hasRecent(int $userId, int $since): bool
    {
        return AbsenceNotificationService::query()->where('user_id', $userId)
            ->where('date', '>=', (string) $since)->exists();
    }

    /** @return list<int> */
    public function supervisorRecipientIds(int $branchId, int $companyId): array
    {
        return User::query()->where('branch_id', $branchId)->where('companies_groups_id', $companyId)
            ->where('activated', 1)->whereIn('hr_user_level', [1, 2, 4])->pluck('hr_id')->map(fn ($id) => (int) $id)->all();
    }

    public function createRequest(array $attributes, array $recipientIds): AbsenceNotificationService
    {
        return DB::transaction(function () use ($attributes, $recipientIds): AbsenceNotificationService {
            $notification = AbsenceNotificationService::create($attributes);
            foreach (array_unique(array_map('intval', $recipientIds)) as $userId) {
                AbsenceNotificationServiceSendTo::create(['memo_id' => $notification->id, 'user_id' => $userId]);
            }
            return $notification;
        });
    }

    /** @return Collection<int, AbsenceNotificationServiceDeathLeaveCategory> */
    public function deathLeaveCategories(): Collection
    {
        return AbsenceNotificationServiceDeathLeaveCategory::query()->where('publish', 1)->orderBy('id')->get(['id', 'name_en', 'name_ar', 'days']);
    }

    public function deathLeaveDays(int $id): ?int
    {
        $days = AbsenceNotificationServiceDeathLeaveCategory::query()->whereKey($id)->where('publish', 1)->value('days');
        return $days === null ? null : (int) $days;
    }

    public function isRecipient(int $notificationId, int $userId): bool
    {
        return AbsenceNotificationServiceSendTo::query()->where('memo_id', $notificationId)->where('user_id', $userId)->exists();
    }
}
