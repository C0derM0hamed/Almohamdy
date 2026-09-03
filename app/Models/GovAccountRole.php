<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovAccountRole extends Model
{
    protected $table = 'gov_account_roles';

    protected $fillable = ['companies_groups_id', 'name_ar', 'name_en', 'publish', 'ranking'];

    protected function casts(): array
    {
        return ['publish' => 'boolean', 'ranking' => 'integer'];
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar' ? (string) ($this->name_ar ?: $this->name_en) : (string) ($this->name_en ?: $this->name_ar);
    }
}
