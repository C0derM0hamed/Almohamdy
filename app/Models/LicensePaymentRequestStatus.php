<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicensePaymentRequestStatus extends Model
{
    public const RECEIVED = 'received';

    public const IN_PROGRESS = 'in_progress';

    public const NEEDS_DOCUMENTS = 'needs_documents';

    public const PAID = 'paid';

    protected $table = 'license_payment_request_statuses';

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = ['code', 'name_ar', 'name_en', 'info', 'publish', 'ranking'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['publish' => 'boolean', 'ranking' => 'integer'];
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(LicensePaymentRequest::class, 'status_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(LicensePaymentEvent::class, 'status_id');
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar'
            ? trim((string) ($this->name_ar ?: $this->name_en))
            : trim((string) ($this->name_en ?: $this->name_ar));
    }

    public function badgeColor(): string
    {
        $color = trim((string) $this->info);

        return $color !== '' ? $color : match ((string) $this->code) {
            self::RECEIVED => '#2563eb',
            self::IN_PROGRESS => '#ca8a04',
            self::NEEDS_DOCUMENTS => '#dc2626',
            self::PAID => '#15803d',
            default => '#64748b',
        };
    }
}
