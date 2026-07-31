<?php

namespace App\Models;

use App\Support\LocaleText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Speciality extends Model
{
    protected $table = 'specialized_clinics';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publish' => 'string',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinics_id');
    }

    public function clinicians(): HasMany
    {
        return $this->hasMany(Clinician::class, 'specialized_clinics_id');
    }

    public function outpatientSections(): HasMany
    {
        return $this->hasMany(OutpatientClinicSection::class, 'specialized_clinics_id');
    }

    public function localizedName(): string
    {
        return LocaleText::localizedField(
            isset($this->subject_ar) ? (string) $this->subject_ar : null,
            isset($this->subject_en) ? (string) $this->subject_en : null,
        );
    }
}
