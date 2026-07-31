<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GovernmentDataRequestEntity extends Model
{
    protected $table = 'g_sections';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function dataTypes(): HasMany
    {
        return $this->hasMany(GovernmentDataRequestDataType::class, 'id_sub');
    }

    public function localizedName(): string
    {
        return trim((string) $this->name) ?: '#'.$this->id;
    }
}
