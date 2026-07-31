<?php

namespace App\Services\EmployeeLeave;

use App\Models\ClientVacationBranchReply;
use App\Models\ClientVacationHrReply;
use App\Models\EmployeeVacation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeaveStatusResolver
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public function resolve(EmployeeVacation $vacation): string
    {
        $hrReply = $this->latestHrReply($vacation);

        if ($hrReply !== null) {
            return $this->mapApprovalStatus((int) $hrReply->status_id);
        }

        $branchReply = $this->latestBranchReply($vacation);

        if ($branchReply !== null) {
            $branchStatus = (int) $branchReply->status_id;

            if ($branchStatus === $this->rejectedStatusId()) {
                return self::REJECTED;
            }

            if (in_array($branchStatus, $this->branchApprovedStatusIds(), true)) {
                return self::PENDING;
            }

            return $this->mapApprovalStatus($branchStatus);
        }

        return self::PENDING;
    }

    public function canProcessBranch(EmployeeVacation $vacation): bool
    {
        if ((int) $vacation->emp_id === (int) session('hr_user_id', 0)) {
            return false;
        }

        $branchReply = $this->latestBranchReply($vacation);

        return $branchReply !== null
            && (int) $branchReply->status_id === $this->pendingStatusId();
    }

    public function canProcessHr(EmployeeVacation $vacation): bool
    {
        $branchReply = $this->latestBranchReply($vacation);

        if ($branchReply === null) {
            return false;
        }

        if (! in_array((int) $branchReply->status_id, $this->branchApprovedStatusIds(), true)) {
            return false;
        }

        return $this->latestHrReply($vacation) === null;
    }

    public function approvedStatusId(): int
    {
        return (int) config('hm.employee_leave.approval_status_ids.approved', 1);
    }

    public function rejectedStatusId(): int
    {
        return (int) config('hm.employee_leave.approval_status_ids.rejected', 2);
    }

    public function pendingStatusId(): int
    {
        return (int) config('hm.employee_leave.approval_status_ids.pending', 4);
    }

    /**
     * @return list<int>
     */
    public function branchApprovedStatusIds(): array
    {
        return [
            $this->approvedStatusId(),
            (int) config('hm.employee_leave.approval_status_ids.partial', 3),
        ];
    }

    public function latestBranchReply(EmployeeVacation $vacation): ?ClientVacationBranchReply
    {
        return $vacation->branchReplies->sortByDesc('id')->first();
    }

    public function latestHrReply(EmployeeVacation $vacation): ?ClientVacationHrReply
    {
        return $vacation->hrReplies->sortByDesc('id')->first();
    }

    private function mapApprovalStatus(int $statusId): string
    {
        return match ($statusId) {
            $this->approvedStatusId(), (int) config('hm.employee_leave.approval_status_ids.partial', 3) => self::APPROVED,
            $this->rejectedStatusId() => self::REJECTED,
            $this->pendingStatusId() => self::PENDING,
            default => self::PENDING,
        };
    }

    /**
     * @return array{total: int, pending: int, approved: int, rejected: int}
     */
    public function summarize(Collection $requests): array
    {
        $summary = [
            'total' => $requests->count(),
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        foreach ($requests as $request) {
            $status = $this->resolve($request);
            $summary[$status]++;
        }

        return $summary;
    }

    /**
     * @return list<array{stage: string, status_id: int, status_label: string, comment: string, date: ?Carbon, at: string}>
     */
    public function history(EmployeeVacation $vacation): array
    {
        $events = [];

        foreach ($vacation->branchReplies as $reply) {
            $events[] = [
                'stage' => 'branch',
                'status_id' => (int) $reply->status_id,
                'status_label' => $reply->status?->localizedName() ?? '',
                'comment' => trim((string) $reply->comment),
                'date' => $reply->repliedAt(),
                'at' => $reply->repliedAt()?->format('Y-m-d H:i') ?? '—',
            ];
        }

        foreach ($vacation->hrReplies as $reply) {
            $events[] = [
                'stage' => 'hr',
                'status_id' => (int) $reply->status_id,
                'status_label' => $reply->status?->localizedName() ?? '',
                'comment' => trim((string) $reply->comment),
                'date' => $reply->repliedAt(),
                'at' => $reply->repliedAt()?->format('Y-m-d H:i') ?? '—',
            ];
        }

        usort($events, function (array $a, array $b) {
            $aTime = $a['date']?->timestamp ?? 0;
            $bTime = $b['date']?->timestamp ?? 0;

            return $aTime <=> $bTime;
        });

        return $events;
    }
}
