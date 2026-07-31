<?php

namespace App\Services\WorkAbsenceNotification;

use App\Models\AbsenceNotificationService;
use App\Models\AbsenceNotificationServiceActionType;
use App\Models\AbsenceNotificationServiceMemo;
use App\Models\AbsenceNotificationServiceType;
use App\Repositories\WorkAbsenceNotification\AbsenceNotificationRepository;
use App\Services\Auth\PermissionService;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkAbsenceNotificationService
{
    private const CERTIFICATE_DIRECTORY = 'absence-notification-certificates';
    public function __construct(
        private readonly AbsenceNotificationRepository $repository,
        private readonly AbsenceNotificationWorkflowResolver $workflowResolver,
        private readonly AbsenceNotificationTimelineBuilder $timelineBuilder,
        private readonly AbsenceNotificationMemoService $memoService,
        private readonly PermissionService $permissions,
    ) {}

    /**
     * @return array{
     *     total: int,
     *     pending: int,
     *     action_taken: int,
     *     activated: int,
     *     this_month: int,
     *     recipients_total: int,
     *     recipients_viewed: int,
     *     recipients_pending_view: int
     * }
     */
    public function dashboardSummary(): array
    {
        return array_merge(
            $this->repository->dashboardSummaryCounts(),
            $this->memoService->recipientDashboardCounts(),
        );
    }

    /**
     * @return array{
     *     pending_by_type: list<array{type_id: int, label: string, total: int}>,
     *     action_taken_by_type: list<array{type_id: int, label: string, total: int}>,
     *     activated_by_type: list<array{type_id: int, label: string, total: int}>,
     *     last_30_days: list<array{date: string, label: string, total: int}>,
     *     top_absence_reasons: list<array{label: string, total: int}>
     * }
     */
    public function dashboardReports(): array
    {
        return [
            'pending_by_type' => $this->formatTypeBreakdown($this->repository->pendingByNotificationType()),
            'action_taken_by_type' => $this->formatTypeBreakdown($this->repository->actionTakenByNotificationType()),
            'activated_by_type' => $this->formatTypeBreakdown($this->repository->activatedByNotificationType()),
            'last_30_days' => $this->formatLast30DaysTrend($this->repository->last30DaysTrend()),
            'top_absence_reasons' => $this->repository->topAbsenceReasons()
                ->map(fn (object $row): array => [
                    'label' => (string) $row->absence_reason,
                    'total' => (int) $row->total,
                ])
                ->all(),
        ];
    }

    /**
     * @param array{total: int, pending: int, action_taken: int, activated: int, this_month: int} $summary
     * @param array{
     *     pending_by_type: list<array{type_id: int, label: string, total: int}>,
     *     action_taken_by_type: list<array{type_id: int, label: string, total: int}>,
     *     activated_by_type: list<array{type_id: int, label: string, total: int}>,
     *     last_30_days: list<array{date: string, label: string, total: int}>,
     *     top_absence_reasons: list<array{label: string, total: int}>
     * } $reports
     * @return array{
     *     trend: array{labels: list<string>, values: list<int>, has_data: bool},
     *     type_distribution: array{labels: list<string>, values: list<int>, has_data: bool},
     *     workflow_distribution: array{labels: list<string>, values: list<int>, keys: list<string>, has_data: bool}
     * }
     */
    public function dashboardCharts(array $summary, array $reports): array
    {
        $trendValues = array_column($reports['last_30_days'], 'total');

        return [
            'trend' => [
                'labels' => array_column($reports['last_30_days'], 'label'),
                'values' => $trendValues,
                'has_data' => array_sum($trendValues) > 0,
            ],
            'type_distribution' => $this->chartTypeDistribution(
                $reports['pending_by_type'],
                $reports['action_taken_by_type'],
                $reports['activated_by_type'],
            ),
            'workflow_distribution' => $this->chartWorkflowDistribution($summary),
        ];
    }

    /**
     * @param Collection<int, object{memo_types_id: int, name_en: ?string, name_ar: ?string, total: int}> $rows
     * @return list<array{type_id: int, label: string, total: int}>
     */
    private function formatTypeBreakdown(Collection $rows): array
    {
        $useArabic = app()->getLocale() === 'ar';

        return $rows->map(function (object $row) use ($useArabic): array {
            $label = $useArabic
                ? trim((string) ($row->name_ar ?: $row->name_en))
                : trim((string) ($row->name_en ?: $row->name_ar));

            if ($label === '') {
                $label = __('work_absence_notification.reports.unknown_type');
            }

            return [
                'type_id' => (int) $row->memo_types_id,
                'label' => $label,
                'total' => (int) $row->total,
            ];
        })->all();
    }

    /**
     * @param Collection<int, object{report_date: string, total: int}> $rows
     * @return list<array{date: string, label: string, total: int}>
     */
    private function formatLast30DaysTrend(Collection $rows): array
    {
        $indexed = $rows->keyBy('report_date');
        $trend = [];

        for ($dayOffset = 29; $dayOffset >= 0; $dayOffset--) {
            $date = Carbon::now()->subDays($dayOffset)->format('Y-m-d');
            $trend[] = [
                'date' => $date,
                'label' => Carbon::parse($date)->translatedFormat('M j'),
                'total' => (int) ($indexed->get($date)?->total ?? 0),
            ];
        }

        return $trend;
    }

    /**
     * @param list<array{type_id: int, label: string, total: int}> ...$breakdowns
     * @return array{labels: list<string>, values: list<int>, has_data: bool}
     */
    private function chartTypeDistribution(array ...$breakdowns): array
    {
        /** @var array<int, array{label: string, total: int}> $merged */
        $merged = [];

        foreach ($breakdowns as $rows) {
            foreach ($rows as $row) {
                $typeId = (int) $row['type_id'];

                if (! isset($merged[$typeId])) {
                    $merged[$typeId] = [
                        'label' => $row['label'],
                        'total' => 0,
                    ];
                }

                $merged[$typeId]['total'] += (int) $row['total'];
            }
        }

        uasort($merged, fn (array $a, array $b) => $b['total'] <=> $a['total']);

        $values = array_column($merged, 'total');

        return [
            'labels' => array_column($merged, 'label'),
            'values' => $values,
            'has_data' => array_sum($values) > 0,
        ];
    }

    /**
     * @param array{total: int, pending: int, action_taken: int, activated: int, this_month: int} $summary
     * @return array{labels: list<string>, values: list<int>, keys: list<string>, has_data: bool}
     */
    private function chartWorkflowDistribution(array $summary): array
    {
        $segments = [
            [
                'key' => AbsenceNotificationWorkflowResolver::PENDING,
                'label' => __('work_absence_notification.status.pending'),
                'total' => (int) $summary['pending'],
            ],
            [
                'key' => AbsenceNotificationWorkflowResolver::ACTION_TAKEN,
                'label' => __('work_absence_notification.status.action_taken'),
                'total' => (int) $summary['action_taken'],
            ],
            [
                'key' => AbsenceNotificationWorkflowResolver::ACTIVATED,
                'label' => __('work_absence_notification.status.activated'),
                'total' => (int) $summary['activated'],
            ],
        ];

        $segments = array_values(array_filter($segments, fn (array $segment) => $segment['total'] > 0));
        $values = array_column($segments, 'total');

        return [
            'labels' => array_column($segments, 'label'),
            'values' => $values,
            'keys' => array_column($segments, 'key'),
            'has_data' => array_sum($values) > 0,
        ];
    }

    /**
     * @return LengthAwarePaginator<int, AbsenceNotificationService>
     */
    public function listPaginated(
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?int $notificationTypeId,
        string $employeeSearch,
        ?string $workflowStatus,
    ): LengthAwarePaginator {
        $perPage = (int) config('hm.work_absence_notification.per_page', 15);

        return $this->repository->paginateFiltered(
            $dateFrom,
            $dateTo,
            $notificationTypeId,
            $employeeSearch,
            $workflowStatus,
            $perPage,
        );
    }

    public function findForDetail(int $id): ?AbsenceNotificationService
    {
        return $this->repository->findForDetail($id);
    }

    public function canProcess(AbsenceNotificationService $notification): bool
    {
        return $this->workflowResolver->resolve($notification) === AbsenceNotificationWorkflowResolver::PENDING;
    }

    public function canActivate(AbsenceNotificationService $notification): bool
    {
        return $this->workflowResolver->resolve($notification) === AbsenceNotificationWorkflowResolver::ACTION_TAKEN;
    }

    public function processAction(int $notificationId, int $actionTypeId): void
    {
        $notification = $this->repository->findForProcessing($notificationId);

        if ($notification === null) {
            throw ValidationException::withMessages([
                'notification' => __('work_absence_notification.errors.notification_not_found'),
            ]);
        }

        if (! $this->canProcess($notification)) {
            throw ValidationException::withMessages([
                'action_type' => __('work_absence_notification.errors.not_pending'),
            ]);
        }

        $actionBy = (int) session('hr_user_id', 0);

        $updated = $this->repository->updateAction(
            $notificationId,
            $actionTypeId,
            $actionBy,
            (string) time(),
        );

        if (! $updated) {
            throw ValidationException::withMessages([
                'action_type' => __('work_absence_notification.errors.process_failed'),
            ]);
        }
    }

    public function activate(int $notificationId): void
    {
        $notification = $this->repository->findForProcessing($notificationId);

        if ($notification === null) {
            throw ValidationException::withMessages([
                'notification' => __('work_absence_notification.errors.notification_not_found'),
            ]);
        }

        $status = $this->workflowResolver->resolve($notification);

        if ($status === AbsenceNotificationWorkflowResolver::PENDING) {
            throw ValidationException::withMessages([
                'notification' => __('work_absence_notification.errors.cannot_activate_pending'),
            ]);
        }

        if ($status === AbsenceNotificationWorkflowResolver::ACTIVATED) {
            throw ValidationException::withMessages([
                'notification' => __('work_absence_notification.errors.already_activated'),
            ]);
        }

        $updated = $this->repository->updateActivation(
            $notificationId,
            (int) session('hr_user_id', 0),
            Carbon::now()->toDateTimeString(),
        );

        if (! $updated) {
            throw ValidationException::withMessages([
                'notification' => __('work_absence_notification.errors.activation_failed'),
            ]);
        }
    }

    /**
     * @return list<array{stage: string, label: string, actor: string, detail: string, user: string, action_type: string, at: string, sort_at: int}>
     */
    public function buildStatusHistory(AbsenceNotificationService $notification): array
    {
        return $this->timelineBuilder->build($notification);
    }

    /**
     * @return Collection<int, AbsenceNotificationServiceType>
     */
    public function notificationTypeOptions(): Collection
    {
        return $this->repository->notificationTypeOptions();
    }

    /**
     * @return Collection<int, AbsenceNotificationServiceActionType>
     */
    public function actionTypeOptions(): Collection
    {
        return $this->repository->actionTypeOptions();
    }

    public function canCreateMemo(AbsenceNotificationService $notification): bool
    {
        return $this->memoService->canCreate($notification);
    }

    /**
     * @param list<int> $recipientIds
     */
    public function createMemo(
        int $notificationId,
        int $memoTypeId,
        array $recipientIds,
        ?string $beginDate = null,
        ?string $endDate = null,
        ?string $notes = null,
    ): AbsenceNotificationServiceMemo {
        return $this->memoService->create(
            $notificationId,
            $memoTypeId,
            $recipientIds,
            $beginDate,
            $endDate,
            $notes,
        );
    }

    public function memoTypeOptions(): Collection
    {
        return $this->memoService->memoTypeOptions();
    }

    public function memoRecipientOptions(): Collection
    {
        return $this->memoService->recipientOptions();
    }

    /**
     * @return array{total: int, viewed: int, pending_view: int}
     */
    public function recipientStatistics(AbsenceNotificationService $notification): array
    {
        return $this->memoService->recipientStatistics($notification);
    }

    public function listOwnedRequests(): LengthAwarePaginator
    {
        return $this->repository->paginateOwned((int) config('hm.work_absence_notification.per_page', 15));
    }

    public function createRequestOptions(): array
    {
        return [
            'types' => $this->repository->notificationTypeOptions()->whereIn('id', [1, 2, 3, 4, 5]),
            'deathCategories' => $this->repository->deathLeaveCategories(),
        ];
    }

    public function submitRequest(array $data, ?UploadedFile $file = null): AbsenceNotificationService
    {
        $userId = (int) session('hr_user_id', 0);
        $branchId = (int) session('hr_branch_id', 0);
        $companyId = (int) session('companies_groups_id', 0);
        if ($this->repository->hasRecent($userId, time() - 8 * 3600)) {
            throw ValidationException::withMessages(['memo_types_id' => __('work_absence_notification.errors.duplicate_recent')]);
        }

        $type = (int) $data['memo_types_id'];
        if ($type === 4) {
            $days = $this->repository->deathLeaveDays((int) $data['deceased_relationship']);
            if ($days === null) {
                throw ValidationException::withMessages(['deceased_relationship' => __('validation.exists', ['attribute' => 'deceased_relationship'])]);
            }
            $data['end_date'] = Carbon::parse($data['begin_date'])->addDays(max(0, $days - 1))->toDateString();
        }
        $storedFile = null;
        try {
            if ($file !== null) {
                $storedFile = $file->storeAs(self::CERTIFICATE_DIRECTORY, 'absence_'.Str::uuid().'.'.$file->guessExtension(), 'local');
            }
            $attributes = array_merge($data, [
                'branch_id' => $branchId, 'companies_groups_id' => $companyId, 'user_id' => $userId,
                'date' => (string) time(), 'sms_tocken' => 'memo'.bin2hex(random_bytes(24)),
                'sick_leave_file' => $storedFile ? basename($storedFile) : null,
            ]);
            $notification = $this->repository->createRequest($attributes, $this->repository->supervisorRecipientIds($branchId, $companyId));
            return $notification;
        } catch (\Throwable $e) {
            if ($storedFile !== null) Storage::disk('local')->delete($storedFile);
            throw $e;
        }
    }

    public function attachmentForUser(int $id): array
    {
        $notification = $this->repository->findOwned($id);
        $userId = (int) session('hr_user_id', 0);
        if ($notification === null) {
            $notification = $this->repository->findForDetail($id);
            if ($notification === null || ((int) $notification->user_id !== $userId && ! $this->repository->isRecipient($id, $userId) && ! $this->permissions->can(WorkAbsenceNotificationPermissions::VIEW))) {
                abort(403);
            }
        }
        $name = $notification->attachmentFileName();
        abort_if($name === '', 404);
        $private = self::CERTIFICATE_DIRECTORY.'/'.$name;
        if (Storage::disk('local')->exists($private)) return [Storage::disk('local')->path($private), $name];
        $legacy = public_path('absence_notification_service_files/'.$name);
        abort_unless(is_file($legacy), 404);
        return [$legacy, $name];
    }
}
