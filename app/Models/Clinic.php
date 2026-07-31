<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    protected $table = 'clinics';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function specialities(): HasMany
    {
        return $this->hasMany(Speciality::class, 'clinics_id');
    }

    public function localizedName(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->name_ar ?: $this->name_en));
        }

        return trim((string) ($this->name_en ?: $this->name_ar));
    }
}
