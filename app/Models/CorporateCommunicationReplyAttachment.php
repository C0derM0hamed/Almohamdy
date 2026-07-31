<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorporateCommunicationReplyAttachment extends Model
{
    protected $table = 'corporate_communications_replies_attachments';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'corporate_communications_replies_id',
        'file',
        'file_name',
    ];

    public function reply(): BelongsTo
    {
        return $this->belongsTo(CorporateCommunicationReply::class, 'corporate_communications_replies_id');
    }
}
