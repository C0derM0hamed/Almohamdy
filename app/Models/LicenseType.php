<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseType extends Model
{
    protected $table = 'license_types';

    /** @var list<string> */
    protected $fillable = ['companies_groups_id', 'name_ar', 'name_en', 'publish', 'ranking'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['publish' => 'boolean', 'ranking' => 'integer'];
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class, 'license_type_id');
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar'
            ? trim((string) ($this->name_ar ?: $this->name_en))
            : trim((string) ($this->name_en ?: $this->name_ar));
    }
}
