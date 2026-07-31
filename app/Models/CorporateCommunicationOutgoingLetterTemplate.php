<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorporateCommunicationOutgoingLetterTemplate extends Model
{
    protected $table = 'corporate_communications_outgoing_letters_template';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function localizedTitle(): string
    {
        return trim((string) $this->title);
    }
}
