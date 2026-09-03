<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovAccountStatus extends Model
{
    protected $table = 'gov_account_statuses';

    protected $fillable = ['companies_groups_id', 'code', 'name_ar', 'name_en', 'info', 'publish', 'ranking'];

    protected function casts(): array
    {
        return ['publish' => 'boolean', 'ranking' => 'integer'];
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar' ? (string) ($this->name_ar ?: $this->name_en) : (string) ($this->name_en ?: $this->name_ar);
    }
}
