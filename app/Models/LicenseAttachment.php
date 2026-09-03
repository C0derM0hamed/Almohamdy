<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseAttachment extends Model
{
    public const CONTEXT_LICENSE = 'license';

    public const CONTEXT_RENEWAL = 'renewal';

    public const CONTEXT_PAYMENT = 'payment';

    public const CONTEXT_PAYMENT_PROOF = 'payment_proof';

    public const CONTEXT_COMMENT = 'comment';

    public const CONTEXT_EXTERNAL = 'external';

    protected $table = 'license_attachments';

    public const CREATED_AT = 'uploaded_at';

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'license_id',
        'renewal_id',
        'payment_request_id',
        'context',
        'file_path',
        'original_name',
        'mime',
        'size',
        'description',
        'uploaded_by',
        'uploaded_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['size' => 'integer', 'uploaded_at' => 'datetime'];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    public function renewal(): BelongsTo
    {
        return $this->belongsTo(LicenseRenewal::class, 'renewal_id');
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(LicensePaymentRequest::class, 'payment_request_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'hr_id');
    }

    public function displayLabel(): string
    {
        return trim((string) $this->original_name) ?: '#'.$this->getKey();
    }
}
