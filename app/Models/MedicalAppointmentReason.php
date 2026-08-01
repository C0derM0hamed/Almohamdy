<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalAppointmentReason extends Model
{
    protected $table = 'book_a_medical_appointment_not_wanting';

    public $timestamps = false;

    public function localizedName(): string
    {
        return trim((string) ($this->name_ar ?? $this->name_en ?? $this->name ?? ''));
    }
}
