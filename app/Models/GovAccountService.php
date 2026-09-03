<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovAccountService extends Model
{
    protected $table = 'gov_account_services';

    protected $fillable = ['companies_groups_id', 'authority_id', 'name_ar', 'name_en', 'publish', 'ranking'];

    protected function casts(): array
    {
        return ['publish' => 'boolean', 'ranking' => 'integer'];
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(GovAccountAuthority::class, 'authority_id');
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar' ? (string) ($this->name_ar ?: $this->name_en) : (string) ($this->name_en ?: $this->name_ar);
    }
}
