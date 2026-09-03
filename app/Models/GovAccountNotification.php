<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovAccountNotification extends Model
{
    protected $table = 'gov_account_notifications';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'read_at' => 'datetime'];
    }
}
