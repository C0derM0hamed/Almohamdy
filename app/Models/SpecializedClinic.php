<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecializedClinic extends Model
{
    protected $table = 'specialized_clinics';

    public $timestamps = false;

    public function localizedName(): string
    {
        return trim((string) ($this->subject_ar ?? $this->subject_en ?? $this->name_ar ?? $this->name_en ?? ''));
    }
}
