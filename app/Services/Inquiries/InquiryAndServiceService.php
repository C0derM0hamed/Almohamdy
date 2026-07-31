<?php

namespace App\Services\Inquiries;

use App\Http\Requests\Inquiries\UpdateInquiryStatusRequest;
use App\Models\InquiryAndService;
use App\Models\InquiryAndServiceStatus;
use App\Models\User;
use App\Repositories\Inquiries\InquiryAndServiceRepository;
use App\Support\Inquiries\InquiryUserNameResolver;
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

    /**
     * @return list<array{id:int,name:string}>
     */
    public function activeUsersForDepartment(int $departmentId): array
    {
        return $this->repository->activeUsersForDepartment($departmentId)
            ->map(fn (User $user) => [
                'id' => (int) $user->hr_id,
                'name' => $user->displayName(),
            ])
            ->values()
            ->all();
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
        return in_array($statusId, config('hm.inquiries.new_status_ids', [999999, 1]), true);
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
        $statusId = (int) $inquiry->status;

        if ($statusId === 3) {
            return true;
        }

        if (! $this->isNewStatus($statusId)) {
            return false;
        }

        // Legacy status 999999 represents both New and Forwarded. A matching
        // timeline reply distinguishes a forwarded (locked) inquiry from New.
        return ! $this->repository->hasForwardedStatusUpdate($inquiry);
    }

    /**
     * @param  array{
     *     status_id:int,
     *     notes:string,
     *     department_id:?int,
     *     assignment_type:?string,
     *     employee_id:?int
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

        if ($isForward) {
            $departmentId = (int) ($payload['department_id'] ?? 0);

            if ($departmentId <= 0 || ! $this->repository->departmentOptions()->contains(
                fn ($branch) => (int) $branch->id === $departmentId
            )) {
                throw ValidationException::withMessages([
                    'department_id' => [__('inquiries.status_form.department_required')],
                ]);
            }

            if (($payload['assignment_type'] ?? null) === UpdateInquiryStatusRequest::ASSIGNMENT_EMPLOYEE) {
                $employeeId = (int) ($payload['employee_id'] ?? 0);
                $allowed = $this->repository->activeUsersForDepartment($departmentId)
                    ->contains(fn (User $user) => (int) $user->hr_id === $employeeId);

                if (! $allowed) {
                    throw ValidationException::withMessages([
                        'employee_id' => [__('inquiries.status_form.employee_invalid')],
                    ]);
                }
            }
        }

        $previousStatusId = (int) $inquiry->status;
        $previousDepartmentId = (int) $inquiry->inquired_section;
        $previousAssigneeId = $inquiry->assigned_to !== null ? (int) $inquiry->assigned_to : null;

        $newDepartmentId = $isForward
            ? (int) $payload['department_id']
            : $previousDepartmentId;

        $newAssigneeId = null;
        $assignmentType = $payload['assignment_type'] ?? null;

        if ($isForward && $assignmentType === UpdateInquiryStatusRequest::ASSIGNMENT_EMPLOYEE) {
            $newAssigneeId = (int) $payload['employee_id'];
        }

        $timelineMessage = $this->buildTimelineAuditMessage(
            previousStatusId: $previousStatusId,
            newStatusId: $statusId,
            previousDepartmentId: $previousDepartmentId,
            newDepartmentId: $newDepartmentId,
            previousAssigneeId: $previousAssigneeId,
            newAssigneeId: $newAssigneeId,
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
        ?int $previousAssigneeId,
        ?int $newAssigneeId,
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
            if ($assignmentType === UpdateInquiryStatusRequest::ASSIGNMENT_EMPLOYEE && $newAssigneeId) {
                $parts[] = __('inquiries.timeline_audit.assignee_employee', [
                    'name' => InquiryUserNameResolver::resolve($newAssigneeId),
                ]);
            } else {
                $parts[] = __('inquiries.timeline_audit.assignee_department');
            }
        } elseif ($previousAssigneeId !== $newAssigneeId) {
            $parts[] = __('inquiries.timeline_audit.assignee', [
                'from' => $previousAssigneeId
                    ? InquiryUserNameResolver::resolve($previousAssigneeId)
                    : __('inquiries.timeline_audit.entire_department'),
                'to' => $newAssigneeId
                    ? InquiryUserNameResolver::resolve($newAssigneeId)
                    : __('inquiries.timeline_audit.entire_department'),
            ]);
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
