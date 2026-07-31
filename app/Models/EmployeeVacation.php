<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeVacation extends Model
{
    protected $table = 'emp_vacations';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(BranchUser::class, 'emp_id');
    }

    public function branchReplies(): HasMany
    {
        return $this->hasMany(ClientVacationBranchReply::class, 'vac_id');
    }

    public function hrReplies(): HasMany
    {
        return $this->hasMany(ClientVacationHrReply::class, 'vac_id');
    }

    public function leaveTypeLabel(): string
    {
        $vacType = (int) $this->vac_type;
        $otherLeaveTypeId = (int) config('hm.employee_leave.other_leave_type_id', 99);

        if ($vacType === $otherLeaveTypeId) {
            $customType = trim((string) $this->other_contacts);

            if ($customType !== '') {
                return $customType;
            }
        }

        $types = config('hm.employee_leave.leave_types', []);
        $type = $types[$vacType] ?? null;

        if ($type === null) {
            return (string) $this->vac_type;
        }

        $locale = app()->getLocale();

        return trim((string) ($type[$locale] ?? $type['en'] ?? $this->vac_type));
    }

    public function employeeDisplayName(): string
    {
        if ($this->relationLoaded('employee') && $this->employee) {
            return $this->employee->displayName();
        }

        if (trim((string) $this->job_title) !== '') {
            return trim((string) $this->job_title);
        }

        return '#'.$this->emp_id;
    }

    public function startDate(): ?Carbon
    {
        $timestamp = (int) $this->started_date;

        return $timestamp > 0 ? Carbon::createFromTimestamp($timestamp)->startOfDay() : null;
    }

    public function endDate(): ?Carbon
    {
        $start = $this->startDate();

        if ($start === null || (int) $this->days <= 0) {
            return null;
        }

        return $start->copy()->addDays((int) $this->days - 1);
    }

    public function formattedStartDate(): string
    {
        return $this->startDate()?->format('Y-m-d') ?? '—';
    }

    public function formattedEndDate(): string
    {
        return $this->endDate()?->format('Y-m-d') ?? '—';
    }

    public function applicationReason(): string
    {
        $reply = $this->branchReplies->sortBy('id')->first();

        return trim((string) ($reply?->comment ?? ''));
    }
}
