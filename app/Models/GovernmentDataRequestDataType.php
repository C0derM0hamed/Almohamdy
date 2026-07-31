<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentDataRequestDataType extends Model
{
    protected $table = 'g_sectionsub';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function entity(): BelongsTo
    {
        return $this->belongsTo(GovernmentDataRequestEntity::class, 'id_sub');
    }

    public function localizedName(): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $this->name) ?? '') ?: '#'.$this->id;
    }
}
