<?php

namespace App\Services\EmployeeLeave;

use App\Models\EmployeeVacation;
use App\Repositories\EmployeeLeave\LeaveRequestRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LeaveRequestService
{
    public function __construct(
        private readonly LeaveRequestRepository $repository,
        private readonly LeaveStatusResolver $statusResolver,
    ) {}

    /**
     * @return LengthAwarePaginator<int, EmployeeVacation>
     */
    public function listPaginated(string $search, ?string $status, ?int $leaveType): LengthAwarePaginator
    {
        $perPage = (int) config('hm.employee_leave.per_page', 15);

        return $this->repository->paginateFiltered(
            $search,
            $status,
            $leaveType,
            $perPage,
            $this->statusResolver,
        );
    }

    /**
     * @return array{total: int, pending: int, approved: int, rejected: int}
     */
    public function dashboardSummary(): array
    {
        return $this->statusResolver->summarize($this->repository->allForDashboard());
    }

    public function findForDetail(int $id): ?EmployeeVacation
    {
        return $this->repository->findForDetail($id);
    }

    public function resolveStatus(EmployeeVacation $vacation): string
    {
        return $this->statusResolver->resolve($vacation);
    }

    public function canProcessBranch(EmployeeVacation $vacation): bool
    {
        return $this->statusResolver->canProcessBranch($vacation);
    }

    public function canProcessHr(EmployeeVacation $vacation): bool
    {
        return $this->statusResolver->canProcessHr($vacation);
    }

    public function processBranchDecision(int $leaveId, string $decision, string $comment): EmployeeVacation
    {
        $record = $this->repository->findForDetail($leaveId);

        abort_if($record === null, 404);
        abort_unless($this->canProcessBranch($record), 403);

        $statusId = $decision === 'approve'
            ? $this->statusResolver->approvedStatusId()
            : $this->statusResolver->rejectedStatusId();

        $this->repository->addBranchDecision(
            $leaveId,
            $statusId,
            $comment,
            (int) session('hr_user_id', 0),
        );

        return $this->repository->findForDetail($leaveId) ?? $record;
    }

    public function processHrDecision(int $leaveId, string $decision, string $comment): EmployeeVacation
    {
        $record = $this->repository->findForDetail($leaveId);

        abort_if($record === null, 404);
        abort_unless($this->canProcessHr($record), 403);

        $statusId = $decision === 'approve'
            ? $this->statusResolver->approvedStatusId()
            : $this->statusResolver->rejectedStatusId();

        $this->repository->addHrDecision(
            $leaveId,
            $statusId,
            $comment,
            (int) session('hr_user_id', 0),
        );

        return $this->repository->findForDetail($leaveId) ?? $record;
    }

    /**
     * @return list<array{stage: string, status_id: int, status_label: string, comment: string, date: ?Carbon, at: string}>
     */
    public function statusHistory(EmployeeVacation $vacation): array
    {
        return $this->statusResolver->history($vacation);
    }

    /**
     * @return array<string, string>
     */
    public function leaveTypeOptions(): array
    {
        $types = config('hm.employee_leave.leave_types', []);
        $locale = app()->getLocale();
        $options = [];

        foreach ($types as $id => $labels) {
            $options[(int) $id] = trim((string) ($labels[$locale] ?? $labels['en'] ?? $id));
        }

        return $options;
    }

    public function otherLeaveTypeId(): int
    {
        return (int) config('hm.employee_leave.other_leave_type_id', 99);
    }

    public function storeApplication(
        int $leaveType,
        Carbon $startDate,
        Carbon $endDate,
        string $reason,
        ?string $leaveTypeOther = null,
    ): EmployeeVacation {
        $days = $startDate->diffInDays($endDate) + 1;
        $submitterId = (int) session('hr_user_id', 0);
        $otherLeaveTypeId = $this->otherLeaveTypeId();

        return $this->repository->create([
            'branch_id' => (int) session('hr_branch_id', 0),
            'companies_groups_id' => (int) session('companies_groups_id', 0),
            'emp_id' => $submitterId,
            'vac_type' => $leaveType,
            'days' => $days,
            'date' => (string) time(),
            'started_date' => (string) $startDate->startOfDay()->timestamp,
            'direct_date' => $endDate->copy()->addDay()->startOfDay()->timestamp,
            'branch_approval' => 0,
            'hr_approval' => null,
            'publish' => 0,
            'nationality' => null,
            'job_title' => trim((string) session('job_title', '')),
            'contacts' => trim((string) session('mobile', '')),
            'email' => trim((string) session('email', '')),
            'other_contacts' => $leaveType === $otherLeaveTypeId
                ? trim((string) $leaveTypeOther)
                : null,
        ], $reason, $submitterId);
    }
}
