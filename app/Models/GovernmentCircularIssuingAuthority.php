<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovernmentCircularIssuingAuthority extends Model
{
    protected $table = 'government_circulars_issuing_authority';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function localizedName(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->name_ar ?: $this->name_en));
        }

        return trim((string) ($this->name_en ?: $this->name_ar));
    }
}
