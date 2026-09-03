<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseTimeline extends Model
{
    protected $table = 'license_timeline';

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'license_id',
        'event_type',
        'status_id',
        'notice',
        'meta',
        'created_by',
        'created_by_type',
        'branch_id',
        'date',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['meta' => 'array', 'date' => 'datetime'];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(LicenseStatus::class, 'status_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'hr_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
