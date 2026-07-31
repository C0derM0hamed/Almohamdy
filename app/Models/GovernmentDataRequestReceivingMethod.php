<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovernmentDataRequestReceivingMethod extends Model
{
    protected $table = 'g_requestdelivry';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function localizedName(): string
    {
        return trim((string) $this->name) ?: '#'.$this->id;
    }
}
