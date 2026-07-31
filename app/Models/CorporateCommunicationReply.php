<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorporateCommunicationReply extends Model
{
    protected $table = 'corporate_communications_replies';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'corporate_communications_id',
        'branch_id',
        'details',
        'created_by',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function communication(): BelongsTo
    {
        return $this->belongsTo(CorporateCommunication::class, 'corporate_communications_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CorporateCommunicationReplyAttachment::class, 'corporate_communications_replies_id');
    }
}
