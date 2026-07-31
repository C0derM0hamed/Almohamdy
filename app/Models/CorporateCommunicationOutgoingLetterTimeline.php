<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorporateCommunicationOutgoingLetterTimeline extends Model
{
    protected $table = 'corporate_communications_outgoing_letters_timeline';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'corporate_communications_outgoing_letters_id',
        'status_id',
        'date',
        'notice',
        'created_by',
        'created_by_type',
        'branch_id',
    ];

    public function letter(): BelongsTo
    {
        return $this->belongsTo(
            CorporateCommunicationOutgoingLetter::class,
            'corporate_communications_outgoing_letters_id'
        );
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(CorporateCommunicationOutgoingLetterStatus::class, 'status_id');
    }
}
