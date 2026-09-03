<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovAccountTimeline extends Model
{
    protected $table = 'gov_account_timeline';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'date' => 'datetime'];
    }
}
