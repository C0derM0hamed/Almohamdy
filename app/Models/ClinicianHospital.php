<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicianHospital extends Model
{
    protected $table = 'clinician_hospitals';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function clinician(): BelongsTo
    {
        return $this->belongsTo(Clinician::class, 'clinicians_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class, 'hospital_id');
    }

    public function outpatientClinic(): BelongsTo
    {
        return $this->belongsTo(OutpatientClinic::class, 'clinics_id');
    }

    public function clinicBuildingLabel(): string
    {
        if ($this->outpatientClinic === null) {
            return '';
        }

        return app(\App\Services\ServiceLocations\ServiceLocationService::class)
            ->locationLabel($this->outpatientClinic);
    }

    public function clinicNumberLabel(): string
    {
        return trim((string) ($this->clinic_number ?: $this->ext_number));
    }
}

