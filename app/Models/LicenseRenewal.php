<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseRenewal extends Model
{
    protected $table = 'license_renewals';

    /** @var list<string> */
    protected $fillable = [
        'license_id',
        'previous_expiry_date',
        'new_expiry_date',
        'started_at',
        'completed_at',
        'completed_by',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'previous_expiry_date' => 'date',
            'new_expiry_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Keep SQL DATE columns date-only on every supported driver.  Laravel's
     * generic date casting otherwise serializes as a datetime on SQLite,
     * which makes renewal-history exports and direct date comparisons drift.
     */
    public function setPreviousExpiryDateAttribute(mixed $value): void
    {
        $this->attributes['previous_expiry_date'] = $this->dateOnly($value);
    }

    public function setNewExpiryDateAttribute(mixed $value): void
    {
        $this->attributes['new_expiry_date'] = $value === null ? null : $this->dateOnly($value);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by', 'hr_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LicenseAttachment::class, 'renewal_id');
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(LicensePaymentRequest::class, 'renewal_id');
    }

    public function isOpen(): bool
    {
        return $this->completed_at === null;
    }

    private function dateOnly(mixed $value): string
    {
        return $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : CarbonImmutable::parse((string) $value)->toDateString();
    }
}
