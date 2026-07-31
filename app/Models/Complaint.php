<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    protected $table = 'complaints';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public function department(): BelongsTo
    {
        return $this->belongsTo(BranchDepartment::class, 'branches_departments_id');
    }

    public function currentStatus(): BelongsTo
    {
        return $this->belongsTo(ComplaintStatus::class, 'status');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ComplaintReply::class, 'complaints_id');
    }

    public function localizedComplainantName(): string
    {
        if (app()->getLocale() === 'ar') {
            $name = trim((string) ($this->complainant_name_ar ?: $this->complainant_name));

            return $name !== '' ? $name : trim((string) ($this->complainant_name_en ?: $this->complainant_name));
        }

        $name = trim((string) ($this->complainant_name_en ?: $this->complainant_name));

        return $name !== '' ? $name : trim((string) ($this->complainant_name_ar ?: $this->complainant_name));
    }

    public function localizedPatientName(): string
    {
        if (app()->getLocale() === 'ar') {
            $name = trim((string) ($this->patient_name_ar ?: $this->patient_name));

            return $name !== '' ? $name : trim((string) ($this->patient_name_en ?: $this->patient_name));
        }

        $name = trim((string) ($this->patient_name_en ?: $this->patient_name));

        return $name !== '' ? $name : trim((string) ($this->patient_name_ar ?: $this->patient_name));
    }

    public function typeLabel(): string
    {
        $types = config('hm.complaints.types', []);
        $type = $types[(int) $this->type] ?? null;

        if ($type === null) {
            return (string) $this->type;
        }

        $locale = app()->getLocale();

        return trim((string) ($type[$locale] ?? $type['en'] ?? $this->type));
    }

    public function formattedComplaintDate(): string
    {
        $timestamp = (int) $this->date;

        return $timestamp > 0 ? Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i') : '—';
    }

    public function displayNumber(): string
    {
        $number = (int) $this->complaints_numbers_id;

        return $number > 0 ? (string) $number : '#'.$this->id;
    }

    public function numberBadgeColor(): string
    {
        $seed = (int) $this->complaints_numbers_id > 0
            ? (string) $this->complaints_numbers_id
            : (string) $this->id;

        $hash = crc32($seed);
        $hash &= 0xffffffff;

        return '#'.substr('000000'.dechex($hash), -6);
    }

    public function priorityLabel(): string
    {
        $priorities = config('hm.complaints.priorities', []);
        $priority = $priorities[(int) $this->priority] ?? null;

        if ($priority === null) {
            return '—';
        }

        $locale = app()->getLocale();

        return trim((string) ($priority[$locale] ?? $priority['en'] ?? '—'));
    }

    public function formattedUpdatedAt(): string
    {
        $timestamp = self::parseLegacyTimestamp($this->updated_at);

        if ($timestamp === null) {
            return '—';
        }

        return $timestamp->isToday()
            ? __('complaints.insights.today_at', ['time' => $timestamp->format('H:i')])
            : $timestamp->format('Y-m-d H:i');
    }

    private static function parseLegacyTimestamp(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        if (ctype_digit($raw)) {
            $seconds = (int) $raw;

            return $seconds > 0 ? Carbon::createFromTimestamp($seconds) : null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
