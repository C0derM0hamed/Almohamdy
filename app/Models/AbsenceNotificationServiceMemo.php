<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbsenceNotificationServiceMemo extends Model
{
    protected $table = 'absence_notification_service_memo';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(AbsenceNotificationService::class, 'absence_notification_service_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'hr_id');
    }

    public function memoType(): BelongsTo
    {
        return $this->belongsTo(MemoType::class, 'memo_type');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AbsenceNotificationServiceSendTo::class, 'memo_id');
    }

    public function memoTypeLabel(): string
    {
        if ($this->relationLoaded('memoType') && $this->memoType) {
            return $this->memoType->localizedName();
        }

        return $this->memo_type ? (string) $this->memo_type : '—';
    }

    public function employeeDisplayName(): string
    {
        if ($this->relationLoaded('employee') && $this->employee) {
            return $this->employee->displayName();
        }

        return $this->user_id ? '#'.$this->user_id : '—';
    }

    public function formattedDate(): string
    {
        $timestamp = (int) $this->date;

        return $timestamp > 0 ? Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i') : '—';
    }

    public function formattedBeginDate(): string
    {
        $value = trim((string) $this->begin_date);

        return $value !== '' ? $value : '—';
    }

    public function formattedEndDate(): string
    {
        $value = trim((string) $this->end_date);

        return $value !== '' ? $value : '—';
    }

    public function notesText(): string
    {
        $value = trim((string) $this->pending_inquiries);

        return $value !== '' ? $value : '—';
    }
}
