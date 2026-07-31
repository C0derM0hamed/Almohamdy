<?php

namespace App\Models;

use App\Support\LocaleText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutpatientClinic extends Model
{
    protected $table = 'outpatient_clinics';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function sections(): HasMany
    {
        return $this->hasMany(OutpatientClinicSection::class, 'outpatient_clinics_id');
    }

    public function localizedName(): string
    {
        return LocaleText::localizedField(
            isset($this->name_ar) ? (string) $this->name_ar : null,
            isset($this->name_en) ? (string) $this->name_en : null,
        );
    }
}
