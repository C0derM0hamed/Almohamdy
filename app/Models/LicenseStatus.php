<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseStatus extends Model
{
    public const ACTIVE = 'active';

    public const NEAR_EXPIRY = 'near_expiry';

    public const UNDER_RENEWAL = 'under_renewal';

    public const EXPIRED = 'expired';

    public const RENEWED = 'renewed';

    protected $table = 'license_statuses';

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = ['code', 'name_ar', 'name_en', 'info', 'publish', 'ranking'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['publish' => 'boolean', 'ranking' => 'integer'];
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class, 'status_id');
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
            self::ACTIVE => '#15803d',
            self::NEAR_EXPIRY => '#ca8a04',
            self::UNDER_RENEWAL => '#2563eb',
            self::EXPIRED => '#dc2626',
            self::RENEWED => '#0f766e',
            default => '#64748b',
        };
    }
}
