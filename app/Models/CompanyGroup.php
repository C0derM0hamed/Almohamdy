<?php

namespace App\Models;

use App\Support\LocaleText;
use Illuminate\Database\Eloquent\Model;

class CompanyGroup extends Model
{
    protected $table = 'companies_groups';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function localizedName(): string
    {
        return LocaleText::localizedField(
            isset($this->name_ar) ? (string) $this->name_ar : null,
            isset($this->name_en) ? (string) $this->name_en : null,
        );
    }
}
