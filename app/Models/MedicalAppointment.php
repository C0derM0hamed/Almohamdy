<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalAppointment extends Model
{
    protected $table = 'book_a_medical_appointment';

    public $timestamps = false;

    protected $fillable = [
        'date',
        'branch_id',
        'mobile',
        'department',
        'file_number',
        'patient_name',
        'physician',
        'procedure_type',
        'procedure_place',
        'procedure_duration',
        'medical_coverage_status',
        'created_by',
        'companies_groups_id',
        'token',
        'status',
        'language',
        'language_doctor',
        'patient_name_en',
        'procedure_type_en',
        'procedure_duration_en',
        'patient_confirm_date',
        'patient_confirm_date_timestamp',
        'patient_confirm_date_notice',
        'doctor_action',
        'doctor_action_timestmap',
        'cleint_cancel_reason',
    ];

    protected $casts = [
        'date' => 'integer',
        'patient_confirm_date' => 'integer',
        'patient_confirm_date_timestamp' => 'integer',
        'doctor_action_timestmap' => 'integer',
    ];

    public function statusRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalAppointmentStatus::class, 'status');
    }

    public function times(): HasMany
    {
        return $this->hasMany(MedicalAppointmentTime::class, 'book_a_medical_appointment_id');
    }

    public function timelineEntries(): HasMany
    {
        return $this->hasMany(MedicalAppointmentTimeline::class, 'book_a_medical_appointment_id');
    }

    public function physicianRecord(): BelongsTo
    {
        return $this->belongsTo(Clinician::class, 'physician');
    }

    public function departmentRecord(): BelongsTo
    {
        return $this->belongsTo(SpecializedClinic::class, 'department');
    }

    public function procedurePlaceRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalAppointmentProcedurePlace::class, 'procedure_place');
    }

    public function coverageStatusRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalAppointmentCoverageStatus::class, 'medical_coverage_status');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'hr_id');
    }

    public function localizedPatientName(): string
    {
        return app()->getLocale() === 'en'
            ? trim((string) ($this->patient_name_en ?: $this->patient_name))
            : trim((string) ($this->patient_name ?: $this->patient_name_en));
    }

    public function localizedProcedureType(): string
    {
        return app()->getLocale() === 'en'
            ? trim((string) ($this->procedure_type_en ?: $this->procedure_type))
            : trim((string) ($this->procedure_type ?: $this->procedure_type_en));
    }

    public function localizedProcedureDuration(): string
    {
        return app()->getLocale() === 'en'
            ? trim((string) ($this->procedure_duration_en ?: $this->procedure_duration))
            : trim((string) ($this->procedure_duration ?: $this->procedure_duration_en));
    }
}
