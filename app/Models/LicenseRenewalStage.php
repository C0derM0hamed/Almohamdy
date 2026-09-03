<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseRenewalStage extends Model
{
    public const NOT_STARTED = 'not_started';

    public const PREPARING = 'preparing';

    public const AWAITING_PAYMENT = 'awaiting_payment';

    public const SUBMITTED = 'submitted';

    public const COMPLETED = 'completed';

    protected $table = 'license_renewal_stages';

    /** @var list<string> */
    protected $fillable = ['code', 'name_ar', 'name_en', 'publish', 'ranking'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['publish' => 'boolean', 'ranking' => 'integer'];
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class, 'renewal_stage_id');
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar'
            ? trim((string) ($this->name_ar ?: $this->name_en))
            : trim((string) ($this->name_en ?: $this->name_ar));
    }
}
