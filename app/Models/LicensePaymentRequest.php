<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicensePaymentRequest extends Model
{
    protected $table = 'license_payment_requests';

    /** @var list<string> */
    protected $fillable = [
        'license_id',
        'renewal_id',
        'amount',
        'currency',
        'bank_name',
        'account_iban',
        'transfer_details',
        'invoice_number',
        'notes',
        'status_id',
        'requested_by',
        'closed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'closed_at' => 'datetime'];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    public function renewal(): BelongsTo
    {
        return $this->belongsTo(LicenseRenewal::class, 'renewal_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(LicensePaymentRequestStatus::class, 'status_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'hr_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(LicensePaymentEvent::class, 'payment_request_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LicenseAttachment::class, 'payment_request_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(LicenseNotification::class, 'payment_request_id');
    }
}
