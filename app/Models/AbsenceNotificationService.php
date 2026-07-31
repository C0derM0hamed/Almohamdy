<?php

namespace App\Models;

use App\Services\WorkAbsenceNotification\AbsenceNotificationWorkflowResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbsenceNotificationService extends Model
{
    protected $table = 'absence_notification_service';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'branch_id',
        'companies_groups_id',
        'user_id',
        'memo_types_id',
        'date',
        'sms_tocken',
        'begin_date',
        'end_date',
        'sick_leave_file',
        'relationship',
        'deceased_relationship',
        'medical_authority',
        'absence_days',
        'absence_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'hr_id');
    }

    public function notificationType(): BelongsTo
    {
        return $this->belongsTo(AbsenceNotificationServiceType::class, 'memo_types_id');
    }

    public function actionType(): BelongsTo
    {
        return $this->belongsTo(AbsenceNotificationServiceActionType::class, 'action_type');
    }

    public function actionByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by', 'hr_id');
    }

    public function activatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by', 'hr_id');
    }

    public function memos(): HasMany
    {
        return $this->hasMany(AbsenceNotificationServiceMemo::class, 'absence_notification_service_id');
    }

    public function employeeDisplayName(): string
    {
        if ($this->relationLoaded('employee') && $this->employee) {
            return $this->employee->displayName();
        }

        return '#'.$this->user_id;
    }

    public function notificationTypeLabel(): string
    {
        if ($this->relationLoaded('notificationType') && $this->notificationType) {
            return $this->notificationType->localizedName();
        }

        return $this->memo_types_id ? (string) $this->memo_types_id : '—';
    }

    public function modelLabel(): string
    {
        return $this->notificationTypeLabel();
    }

    public function absenceTypeLabel(): string
    {
        foreach (['absence_reason', 'relationship', 'deceased_relationship', 'medical_authority'] as $field) {
            $value = trim((string) $this->{$field});

            if ($value !== '') {
                return $value;
            }
        }

        return $this->notificationTypeLabel();
    }

    public function formattedReleaseDate(): string
    {
        $timestamp = $this->timestampValue('date');

        return $timestamp > 0
            ? Carbon::createFromTimestamp($timestamp)->format('d-M-Y')
            : '—';
    }

    public function hasAttachment(): bool
    {
        return trim((string) $this->sick_leave_file) !== '';
    }

    public function attachmentFileName(): string
    {
        $file = trim((string) $this->sick_leave_file);

        return $file !== '' ? basename($file) : '';
    }

    public function actionTypeLabel(): string
    {
        if ($this->relationLoaded('actionType') && $this->actionType) {
            return $this->actionType->localizedName();
        }

        if ((int) $this->action_type <= 0) {
            return '—';
        }

        return (string) $this->action_type;
    }

    public function workflowStatusKey(): string
    {
        return app(AbsenceNotificationWorkflowResolver::class)->resolve($this);
    }

    public function workflowStatusLabel(): string
    {
        return app(AbsenceNotificationWorkflowResolver::class)->label($this->workflowStatusKey());
    }

    public function formattedCreatedDate(): string
    {
        return $this->formatTimestampField('date', 'Y-m-d H:i');
    }

    public function formattedBeginDate(): string
    {
        return $this->formatStoredDateString('begin_date');
    }

    public function formattedEndDate(): string
    {
        return $this->formatStoredDateString('end_date');
    }

    public function formattedActionDate(): string
    {
        return $this->formatTimestampField('action_date', 'Y-m-d H:i');
    }

    public function formattedActivatedAt(): string
    {
        $activatedAt = $this->activatedAtCarbon();

        return $activatedAt !== null ? $activatedAt->format('Y-m-d H:i') : '—';
    }

    public function activatedAtCarbon(): ?Carbon
    {
        $activatedAt = $this->activated_at;

        if ($activatedAt instanceof \DateTimeInterface) {
            return Carbon::instance($activatedAt);
        }

        $value = trim((string) $activatedAt);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function attachmentUrl(): ?string
    {
        $file = trim((string) $this->sick_leave_file);

        if ($file === '') {
            return null;
        }

        if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
            return $file;
        }

        $path = str_starts_with($file, '/') ? $file : '/'.$file;

        return asset(ltrim($path, '/'));
    }

    public function protectedAttachmentUrl(): ?string
    {
        return $this->hasAttachment()
            ? route('modules.work-absence.requests.attachment', $this->id)
            : null;
    }

    public function createdAtCarbon(): ?Carbon
    {
        $timestamp = $this->timestampValue('date');

        return $timestamp > 0 ? Carbon::createFromTimestamp($timestamp) : null;
    }

    private function formatTimestampField(string $field, string $format = 'Y-m-d'): string
    {
        $timestamp = $this->timestampValue($field);

        return $timestamp > 0 ? Carbon::createFromTimestamp($timestamp)->format($format) : '—';
    }

    private function formatStoredDateString(string $field): string
    {
        $value = trim((string) $this->{$field});

        return $value !== '' ? $value : '—';
    }

    private function timestampValue(string $field): int
    {
        return (int) $this->{$field};
    }
}
