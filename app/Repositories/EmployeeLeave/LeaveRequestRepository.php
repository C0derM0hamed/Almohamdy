<?php

namespace App\Repositories\EmployeeLeave;

use App\Models\ApprovalStatus;
use App\Models\ClientVacationBranchReply;
use App\Models\ClientVacationHrReply;
use App\Models\EmployeeVacation;
use App\Services\EmployeeLeave\LeaveStatusResolver;
use App\Support\BranchScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LeaveRequestRepository
{
    /**
     * @var list<string>
     */
    private const LIST_COLUMNS = [
        'id',
        'branch_id',
        'companies_groups_id',
        'emp_id',
        'vac_type',
        'days',
        'date',
        'started_date',
        'direct_date',
        'branch_approval',
        'hr_approval',
        'publish',
        'job_title',
        'email',
        'contacts',
    ];

    /**
     * @var list<string>
     */
    private const DETAIL_COLUMNS = [
        'id',
        'branch_id',
        'companies_groups_id',
        'emp_id',
        'vac_type',
        'days',
        'date',
        'started_date',
        'direct_date',
        'branch_approval',
        'hr_approval',
        'publish',
        'nationality',
        'job_title',
        'contacts',
        'email',
        'other_contacts',
    ];

    public function scopedQuery(): Builder
    {
        $query = EmployeeVacation::query()->select(self::LIST_COLUMNS);

        $companyGroupId = (int) session('companies_groups_id', 0);

        if ($companyGroupId > 0) {
            $query->where('companies_groups_id', $companyGroupId);
        }

        return BranchScope::apply($query);
    }

    /**
     * @return LengthAwarePaginator<int, EmployeeVacation>
     */
    public function paginateFiltered(
        string $search,
        ?string $status,
        ?int $leaveType,
        int $perPage,
        LeaveStatusResolver $statusResolver,
    ): LengthAwarePaginator {
        $query = $this->scopedQuery()
            ->with([
                'employee:id,br_user_full_name',
                'branchReplies' => fn ($q) => $q
                    ->select(['id', 'vac_id', 'status_id', 'date', 'comment'])
                    ->orderByDesc('id')
                    ->limit(1),
                'hrReplies' => fn ($q) => $q
                    ->select(['id', 'vac_id', 'status_id', 'date', 'comment'])
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('id', 'like', '%'.$search.'%')
                        ->orWhere('job_title', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhereHas('employee', fn (Builder $employee) => $employee
                            ->where('br_user_full_name', 'like', '%'.$search.'%'));
                });
            })
            ->when($leaveType !== null, fn (Builder $query) => $query->where('vac_type', $leaveType));

        if ($status !== null && $status !== '') {
            $matchingIds = (clone $query)
                ->orderByDesc('id')
                ->get()
                ->filter(fn (EmployeeVacation $vacation) => $statusResolver->resolve($vacation) === $status)
                ->pluck('id')
                ->all();

            $query->whereIn('id', $matchingIds === [] ? [-1] : $matchingIds);
        }

        return $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, EmployeeVacation>
     */
    public function allForDashboard(): Collection
    {
        return $this->scopedQuery()
            ->with([
                'branchReplies' => fn ($q) => $q
                    ->select(['id', 'vac_id', 'status_id'])
                    ->orderByDesc('id')
                    ->limit(1),
                'hrReplies' => fn ($q) => $q
                    ->select(['id', 'vac_id', 'status_id'])
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->orderByDesc('id')
            ->get();
    }

    public function findForDetail(int $id): ?EmployeeVacation
    {
        $companyGroupId = (int) session('companies_groups_id', 0);

        return EmployeeVacation::query()
            ->select(self::DETAIL_COLUMNS)
            ->when($companyGroupId > 0, fn (Builder $q) => $q->where('companies_groups_id', $companyGroupId))
            ->when((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0,
                fn (Builder $q) => $q->where('branch_id', (int) session('hr_branch_id')))
            ->whereKey($id)
            ->with([
                'employee:id,br_user_full_name,br_user_mobile,br_user_username',
                'branchReplies' => fn ($q) => $q
                    ->select(['id', 'vac_id', 'status_id', 'date', 'comment', 'data_entry_id'])
                    ->orderBy('id'),
                'branchReplies.status:id,name_en,name_ar',
                'hrReplies' => fn ($q) => $q
                    ->select(['id', 'vac_id', 'status_id', 'date', 'comment', 'data_entry_id'])
                    ->orderBy('id'),
                'hrReplies.status:id,name_en,name_ar',
            ])
            ->first();
    }

    public function create(array $attributes, string $reason, int $submitterId): EmployeeVacation
    {
        $vacation = EmployeeVacation::query()->create($attributes);

        ClientVacationBranchReply::query()->create([
            'vac_id' => $vacation->id,
            'status_id' => (int) config('hm.employee_leave.approval_status_ids.pending', 4),
            'date' => (string) time(),
            'comment' => $reason,
            'data_entry_id' => $submitterId,
        ]);

        return $vacation;
    }

    public function addBranchDecision(
        int $vacationId,
        int $statusId,
        string $comment,
        int $adminId,
    ): void {
        ClientVacationBranchReply::query()->create([
            'vac_id' => $vacationId,
            'status_id' => $statusId,
            'date' => (string) time(),
            'comment' => $comment,
            'data_entry_id' => $adminId,
        ]);

        $approvedId = (int) config('hm.employee_leave.approval_status_ids.approved', 1);
        $rejectedId = (int) config('hm.employee_leave.approval_status_ids.rejected', 2);

        EmployeeVacation::query()
            ->whereKey($vacationId)
            ->update([
                'branch_approval' => $statusId === $approvedId ? 1 : ($statusId === $rejectedId ? 2 : 0),
            ]);
    }

    public function addHrDecision(
        int $vacationId,
        int $statusId,
        string $comment,
        int $adminId,
    ): void {
        ClientVacationHrReply::query()->create([
            'vac_id' => $vacationId,
            'status_id' => $statusId,
            'date' => (string) time(),
            'comment' => $comment,
            'data_entry_id' => $adminId,
        ]);

        $approvedId = (int) config('hm.employee_leave.approval_status_ids.approved', 1);
        $rejectedId = (int) config('hm.employee_leave.approval_status_ids.rejected', 2);

        EmployeeVacation::query()
            ->whereKey($vacationId)
            ->update([
                'hr_approval' => $statusId === $approvedId ? 1 : ($statusId === $rejectedId ? 2 : 0),
            ]);
    }

    /**
     * @return Collection<int, ApprovalStatus>
     */
    public function approvalStatuses(): Collection
    {
        return ApprovalStatus::query()
            ->select(['id', 'name_en', 'name_ar'])
            ->where('publish', 1)
            ->orderBy('id')
            ->get();
    }
}
