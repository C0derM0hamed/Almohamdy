<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingConfirmation extends Model
{
    protected $table = 'training_confirmation';

    public $timestamps = false;

    protected $fillable = [
        'branch_id', 'companies_groups_id', 'user_id', 'employee_id', 'training_coordinator',
        'training_hour', 'begin_date', 'days', 'time_from', 'time_to', 'created_at', 'status',
        'type', 'sms_tocken', 'publish',
    ];

    protected $casts = [
        'begin_date' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id', 'hr_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'training_coordinator', 'hr_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'hr_id');
    }

    public function currentStatus(): BelongsTo
    {
        return $this->belongsTo(TrainingConfirmationStatus::class, 'status');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(TrainingConfirmationAction::class, 'training_confirmation_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function endDate(): ?Carbon
    {
        if (! $this->begin_date) {
            return null;
        }

        $date = $this->begin_date->copy();
        $remaining = max(0, (int) $this->days - 1);

        while ($remaining > 0) {
            $date->addDay();
            if (! $date->isFriday()) {
                $remaining--;
            }
        }

        return $date;
    }

    public function hasSignedPdf(): bool
    {
        return trim((string) $this->emdha_output) !== '';
    }
}
