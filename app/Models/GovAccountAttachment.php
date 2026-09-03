<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovAccountAttachment extends Model
{
    protected $table = 'gov_account_attachments';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime', 'size' => 'integer'];
    }
}
