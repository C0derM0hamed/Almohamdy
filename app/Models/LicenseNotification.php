<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseNotification extends Model
{
    public const CHANNEL_IN_APP = 'inapp';

    public const CHANNEL_MAIL = 'mail';

    public const CHANNEL_SMS = 'sms';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_LOGGED = 'logged';

    protected $table = 'license_notifications';

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'license_id',
        'payment_request_id',
        'event_type',
        'recipient_user_id',
        'recipient_email',
        'recipient_mobile',
        'channel',
        'status',
        'error',
        'reason',
        'meta',
        'read_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['meta' => 'array', 'read_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(LicensePaymentRequest::class, 'payment_request_id');
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id', 'hr_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
