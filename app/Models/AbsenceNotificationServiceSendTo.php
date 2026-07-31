<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceNotificationServiceSendTo extends Model
{
    protected $table = 'absence_notification_service_send_to';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * @param Builder<static> $query
     * @return Builder<static>
     */
    public function scopeSeen(Builder $query): Builder
    {
        return $query->whereNotNull('seen_at')->where('seen_at', '!=', '');
    }

    public function memo(): BelongsTo
    {
        return $this->belongsTo(AbsenceNotificationServiceMemo::class, 'memo_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'hr_id');
    }

    public function isSeen(): bool
    {
        return trim((string) $this->seen_at) !== '';
    }

    public function seenStatusKey(): string
    {
        return $this->isSeen() ? 'viewed' : 'pending_view';
    }

    public function seenStatusLabel(): string
    {
        return __('work_absence_notification.recipients.status.'.$this->seenStatusKey());
    }

    public function formattedSeenAt(): string
    {
        $value = trim((string) $this->seen_at);

        if ($value === '') {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable) {
            return '—';
        }
    }
}
