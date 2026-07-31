<?php

namespace App\Models;

use App\Support\LocaleText;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function localizedName(): string
    {
        return LocaleText::localizedField(
            isset($this->country_nationality_ar) ? (string) $this->country_nationality_ar : null,
            isset($this->country_nationality_en) ? (string) $this->country_nationality_en : null,
        );
    }
}
