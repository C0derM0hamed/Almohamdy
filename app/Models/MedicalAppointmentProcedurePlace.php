<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalAppointmentProcedurePlace extends Model
{
    protected $table = 'book_a_medical_appointment_procedure_place';

    public $timestamps = false;

    public function localizedName(): string
    {
        return trim((string) ($this->name_ar ?? $this->name_en ?? $this->name ?? ''));
    }
}
