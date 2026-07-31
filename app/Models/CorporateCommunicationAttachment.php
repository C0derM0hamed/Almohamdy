<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorporateCommunicationAttachment extends Model
{
    protected $table = 'corporate_communications_attachments';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'corporate_communications_id',
        'file',
    ];

    public function communication(): BelongsTo
    {
        return $this->belongsTo(CorporateCommunication::class, 'corporate_communications_id');
    }

    public function displayLabel(): string
    {
        return basename((string) $this->file) ?: '#'.$this->id;
    }
}
