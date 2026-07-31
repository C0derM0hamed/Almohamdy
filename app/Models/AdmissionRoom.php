<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionRoom extends Model
{
    protected $table = 'admission_rooms';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function localizedName(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->name_ar ?: $this->name_en));
        }

        return trim((string) ($this->name_en ?: $this->name_ar));
    }

    public function formattedPrice(): string
    {
        $price = trim((string) $this->price);

        return $price !== '' ? $price : '—';
    }
}
