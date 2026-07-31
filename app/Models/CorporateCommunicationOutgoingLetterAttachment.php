<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorporateCommunicationOutgoingLetterAttachment extends Model
{
    protected $table = 'corporate_communications_outgoing_letters_attachments';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'corporate_communications_outgoing_letters_id',
        'file',
        'file_name',
    ];

    public function letter(): BelongsTo
    {
        return $this->belongsTo(
            CorporateCommunicationOutgoingLetter::class,
            'corporate_communications_outgoing_letters_id'
        );
    }

    public function displayLabel(): string
    {
        $name = trim((string) $this->file_name);

        return $name !== '' ? $name : (basename((string) $this->file) ?: '#'.$this->id);
    }
}
