<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutpatientClinicSection extends Model
{
    protected $table = 'outpatient_clinics_sections';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function speciality(): BelongsTo
    {
        return $this->belongsTo(Speciality::class, 'specialized_clinics_id');
    }

    public function outpatientClinic(): BelongsTo
    {
        return $this->belongsTo(OutpatientClinic::class, 'outpatient_clinics_id');
    }
}
