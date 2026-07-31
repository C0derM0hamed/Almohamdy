<?php

namespace App\Services\Inquiries;

use App\Models\InquiryAndService;
use App\Models\InquiryAndServiceStatus;
use App\Repositories\Inquiries\InquiryAndServiceRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InquiryAndServiceService
{
    public function __construct(
        private readonly InquiryAndServiceRepository $repository,
    ) {}

    /**
     * @return array<string, int>
     */
    public function statusCounts(
        string $direction,
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?int $departmentId,
        string $mobile,
    ): array {
        return $this->repository->statusCounts($direction, $dateFrom, $dateTo, $departmentId, $mobile);
    }

    /**
     * @return LengthAwarePaginator<int, InquiryAndService>
     */
    public function listPaginated(
        string $direction,
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?int $departmentId,
        string $mobile,
        ?int $statusId,
    ): LengthAwarePaginator {
        $perPage = (int) config('hm.inquiries.per_page', 15);

        return $this->repository->paginateFiltered(
            $direction,
            $dateFrom,
            $dateTo,
            $departmentId,
            $mobile,
            $statusId,
            $perPage,
        );
    }

    /**
     * @return Collection<int, InquiryAndServiceStatus>
     */
    public function statusOptions(): Collection
    {
        return $this->repository->statusOptions();
    }

    /**
     * @return list<array{id:int,label:string}>
     */
    public function updateStatusOptions(): array
    {
        return $this->repository->updateStatusOptions();
    }

    public function departmentOptions(): Collection
    {
        return $this->repository->departmentOptions();
    }

    public function inquiryTypeOptions(): Collection
    {
        return $this->repository->inquiryTypeOptions();
    }

    public function jobTitleOptions(): Collection
    {
        return $this->repository->jobTitleOptions();
    }

    /** @param array<string,mixed> $payload */
    public function create(array $payload): InquiryAndService
    {
        return $this->repository->create($payload);
    }

    public function findForDetail(int $id, string $direction): ?InquiryAndService
    {
        return $this->repository->findForDetail($id, $direction);
    }

    public function statusLabel(InquiryAndService $inquiry): string
    {
        return $this->repository->statusLabelById((int) $inquiry->status);
    }

    public function statusColor(InquiryAndService $inquiry): string
    {
        if ($this->isNewStatus((int) $inquiry->status)) {
            return '#dbeafe';
        }

        if ($inquiry->relationLoaded('currentStatus') && $inquiry->currentStatus) {
            return $inquiry->currentStatus->badgeColor();
        }

        return '#e2e8f0';
    }

    public function isNewStatus(int $statusId): bool
    {
        return in_array($statusId, config('hm.inquiries.new_status_ids', [999999, 1, 0]), true);
    }

    public function resolveStatusFilter(?string $statKey): ?int
    {
        if ($statKey === null || $statKey === '') {
            return null;
        }

        if ($statKey === 'new') {
            return 999999;
        }

        $stats = config('hm.inquiries.stat_statuses', []);
        $statusIds = $stats[$statKey] ?? null;

        if (! is_array($statusIds) || $statusIds === []) {
            return null;
        }

        return (int) $statusIds[0];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function timelineEvents(InquiryAndService $inquiry): array
    {
        return app(InquiryTimelineService::class)->build($inquiry);
    }

    public function canUpdateStatus(InquiryAndService $inquiry): bool
    {
        return (int) $inquiry->status !== 6;
    }

    /**
     * @param  array{
     *     status_id:int,
     *     notes:string,
     *     department_id:?int,
     *     assignment_type:?string,
     * }  $payload
     */
    public function updateStatus(InquiryAndService $inquiry, array $payload): InquiryAndService
    {
        if (! $this->canUpdateStatus($inquiry)) {
            throw ValidationException::withMessages([
                'status_id' => [__('inquiries.status_form.locked')],
            ]);
        }

        $forwardStatusId = (int) config('hm.inquiries.forward_status_id', 999999);
        $statusId = (int) $payload['status_id'];
        $isForward = $statusId === $forwardStatusId;

        if (! $isForward && $this->repository->hasStatusReply($inquiry, $statusId)) {
            throw ValidationException::withMessages([
                'status_id' => [__('inquiries.status_form.repeated')],
            ]);
        }

        if ($isForward) {
            $departmentId = (int) ($payload['department_id'] ?? 0);

            if ($departmentId <= 0 || ! $this->repository->departmentOptions()->contains(
                fn ($branch) => (int) $branch->id === $departmentId
            )) {
                throw ValidationException::withMessages([
                    'department_id' => [__('inquiries.status_form.department_required')],
                ]);
            }

        }

        $previousStatusId = (int) $inquiry->status;
        $previousDepartmentId = (int) $inquiry->inquired_section;
        $newDepartmentId = $isForward
            ? (int) $payload['department_id']
            : $previousDepartmentId;

        $assignmentType = $payload['assignment_type'] ?? null;

        $timelineMessage = $this->buildTimelineAuditMessage(
            previousStatusId: $previousStatusId,
            newStatusId: $statusId,
            previousDepartmentId: $previousDepartmentId,
            newDepartmentId: $newDepartmentId,
            assignmentType: $isForward ? (string) $assignmentType : null,
            notes: (string) ($payload['notes'] ?? ''),
            isForward: $isForward,
        );

        return $this->repository->applyStatusUpdate($inquiry, [
            ...$payload,
            'timeline_message' => $timelineMessage,
        ]);
    }

    private function buildTimelineAuditMessage(
        int $previousStatusId,
        int $newStatusId,
        int $previousDepartmentId,
        int $newDepartmentId,
        ?string $assignmentType,
        string $notes,
        bool $isForward,
    ): string {
        $parts = [
            __('inquiries.timeline_audit.status', [
                'from' => $this->actionStatusLabel($previousStatusId),
                'to' => $this->actionStatusLabel($newStatusId, preferForwardLabel: true),
            ]),
            __('inquiries.timeline_audit.department', [
                'from' => $this->repository->departmentLabel($previousDepartmentId),
                'to' => $this->repository->departmentLabel($newDepartmentId),
            ]),
        ];

        if ($isForward) {
            $parts[] = __('inquiries.timeline_audit.assignee_department');
        }

        if ($notes !== '') {
            $parts[] = __('inquiries.timeline_audit.notes', ['notes' => $notes]);
        }

        return implode(' | ', $parts);
    }

    private function actionStatusLabel(int $statusId, bool $preferForwardLabel = false): string
    {
        $forwardStatusId = (int) config('hm.inquiries.forward_status_id', 999999);

        if ($preferForwardLabel && $statusId === $forwardStatusId) {
            return __('inquiries.update_statuses.'.$forwardStatusId);
        }

        if ($this->isNewStatus($statusId) && ! ($preferForwardLabel && $statusId === $forwardStatusId)) {
            return __('inquiries.status.new');
        }

        $key = 'inquiries.update_statuses.'.$statusId;
        $label = __($key);

        if ($label !== $key) {
            return $label;
        }

        return $this->repository->statusLabelById($statusId);
    }
}
