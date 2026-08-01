<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalAppointmentTime extends Model
{
    protected $table = 'book_a_medical_appointment_time';

    public $timestamps = false;

    protected $fillable = ['book_a_medical_appointment_id', 'date'];
}
