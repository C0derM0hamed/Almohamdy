<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicensePaymentEvent extends Model
{
    public const TYPE_STATUS_CHANGED = 'status_changed';

    public const TYPE_DOCUMENTS_REQUESTED = 'docs_requested';

    public const TYPE_COMMENT = 'comment';

    public const TYPE_PROOF_UPLOADED = 'proof_uploaded';

    protected $table = 'license_payment_events';

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['payment_request_id', 'status_id', 'comment', 'event_type', 'created_by'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(LicensePaymentRequest::class, 'payment_request_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(LicensePaymentRequestStatus::class, 'status_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'hr_id');
    }
}
