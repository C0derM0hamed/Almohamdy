<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorporateCommunicationSenderTitle extends Model
{
    protected $table = 'corporate_communications_sendertitle';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function localizedName(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->name_ar ?: $this->name_en));
        }

        return trim((string) ($this->name_en ?: $this->name_ar));
    }
}
