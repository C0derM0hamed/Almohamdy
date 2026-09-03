<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseUndertaking extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_ESCALATED = 'escalated';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'license_undertakings';

    /** @var list<string> */
    protected $fillable = [
        'license_id',
        'user_id',
        'undertaking_text',
        'status',
        'requested_at',
        'accepted_at',
        'escalated_at',
        'ip',
        'user_agent',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'accepted_at' => 'datetime',
            'escalated_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'hr_id');
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED && $this->accepted_at !== null;
    }
}
