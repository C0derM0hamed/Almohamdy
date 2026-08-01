<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalAppointmentTimeline extends Model
{
    protected $table = 'book_a_medical_appointment_timeline';

    public $timestamps = false;

    protected $fillable = ['book_a_medical_appointment_id', 'status_id', 'date', 'notice', 'created_by'];

    public function status(): BelongsTo
    {
        return $this->belongsTo(MedicalAppointmentStatus::class, 'status_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'hr_id');
    }
}
