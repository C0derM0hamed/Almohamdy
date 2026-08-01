<?php

namespace App\Services\Training;

use App\Models\TrainingConfirmation;
use App\Models\TrainingConfirmationAction;
use App\Models\TrainingConfirmationStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrainingService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = $this->scopedQuery()->with(['employee', 'employee.jobTitle', 'currentStatus']);

        if (($filters['from'] ?? '') !== '') {
            $query->where('created_at', '>=', $filters['from'].' 00:00:00');
        }
        if (($filters['to'] ?? '') !== '') {
            $query->where('created_at', '<=', $filters['to'].' 23:59:59');
        }
        if (($filters['status'] ?? 0) > 0) {
            $query->where('status', (int) $filters['status']);
        }
        if (($filters['employee'] ?? '') !== '') {
            $term = trim((string) $filters['employee']);
            $query->whereHas('employee', function (Builder $employee) use ($term): void {
                $employee->where('hr_username', 'like', '%'.$term.'%')
                    ->orWhere('hr_first_name', 'like', '%'.$term.'%')
                    ->orWhere('hr_last_name', 'like', '%'.$term.'%');
            });
        }

        return $query->latest('created_at')->paginate(15)->withQueryString();
    }

    public function find(int $id): ?TrainingConfirmation
    {
        return $this->scopedQuery()
            ->with(['employee.jobTitle', 'coordinator', 'creator', 'currentStatus', 'branch'])
            ->find($id);
    }

    public function statuses(?string $type = null): Collection
    {
        return TrainingConfirmationStatus::query()->where('publish', 1)
            ->when($type !== null, fn (Builder $query) => $query->where('type', $type))
            ->orderBy('id')->get();
    }

    public function employees(): Collection
    {
        return User::query()->where('branch_id', $this->branchId())
            ->where('companies_groups_id', $this->companyId())
            ->orderBy('hr_first_name')->get();
    }

    public function coordinators(): Collection
    {
        return User::query()->select('ra_users.*')->join('user_role', 'ra_users.hr_id', '=', 'user_role.user_id')
            ->where('user_role.role_id', 1)
            ->where('ra_users.branch_id', $this->branchId())
            ->where('ra_users.companies_groups_id', $this->companyId())
            ->distinct()->orderBy('ra_users.hr_first_name')->get();
    }

    public function create(array $data): TrainingConfirmation
    {
        $employee = $this->employees()->firstWhere('hr_id', (int) $data['employee_id']);
        $coordinator = $this->coordinators()->firstWhere('hr_id', (int) $data['training_coordinator']);
        abort_if($employee === null || $coordinator === null, 422);

        return DB::transaction(function () use ($data): TrainingConfirmation {
            $training = TrainingConfirmation::query()->create([
                'branch_id' => $this->branchId(),
                'companies_groups_id' => $this->companyId(),
                'user_id' => $this->userId(),
                'employee_id' => (int) $data['employee_id'],
                'training_coordinator' => (int) $data['training_coordinator'],
                'status' => 1,
                'training_hour' => '8',
                'begin_date' => $data['begin_date'].' 00:00:00',
                'days' => 6,
                'time_from' => $data['time_from'],
                'time_to' => $data['time_to'],
                'created_at' => now()->format('Y-m-d H:i:s'),
                'sms_tocken' => md5(Str::random(40).microtime(true)),
                'publish' => 1,
            ]);

            $this->recordAction($training, 1, $data['details'] ?? null);

            return $training;
        });
    }

    public function updateManagementStatus(TrainingConfirmation $training, int $statusId, ?string $details): void
    {
        $status = TrainingConfirmationStatus::query()->where('publish', 1)->where('type', '2')->find($statusId);
        abort_if($status === null, 422);
        abort_if((int) $training->status === $statusId, 422);

        DB::transaction(function () use ($training, $statusId, $details): void {
            $this->recordAction($training, $statusId, $details);
            $training->forceFill(['status' => $statusId])->save();
        });
    }

    public function updateCoordinationStatus(TrainingConfirmation $training, int $statusId, ?string $details): void
    {
        $status = TrainingConfirmationStatus::query()->where('publish', 1)->where('type', '1')->find($statusId);
        abort_if($status === null, 422);
        abort_if((int) $training->status === $statusId, 422);

        DB::transaction(function () use ($training, $statusId, $details): void {
            $this->recordAction($training, $statusId, $details);
            $training->forceFill(['status' => $statusId])->save();
        });
    }

    public function timeline(TrainingConfirmation $training): Collection
    {
        return TrainingConfirmationAction::query()->with(['status', 'author'])
            ->where('training_confirmation_id', $training->id)
            ->orderByDesc('id')->get();
    }

    private function scopedQuery(): Builder
    {
        return TrainingConfirmation::query()
            ->where('companies_groups_id', $this->companyId())
            ->where('branch_id', $this->branchId())
            ->where('publish', 1);
    }

    private function recordAction(TrainingConfirmation $training, int $statusId, ?string $details): void
    {
        TrainingConfirmationAction::query()->create([
            'training_confirmation_id' => $training->id,
            'status_id' => $statusId,
            'branch_id' => $this->branchId(),
            'details' => $details !== null ? trim($details) : null,
            'created_by' => $this->userId(),
            'created_at' => now(),
        ]);
    }

    private function userId(): int
    {
        return (int) session('hr_user_id');
    }

    private function branchId(): int
    {
        return (int) session('hr_branch_id');
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id');
    }
}
