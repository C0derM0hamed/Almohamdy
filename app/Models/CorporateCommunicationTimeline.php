<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorporateCommunicationTimeline extends Model
{
    protected $table = 'corporate_communications_timeline';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'corporate_communications_id',
        'status_id',
        'date',
        'notice',
        'created_by',
        'created_by_type',
        'branch_id',
    ];

    public function communication(): BelongsTo
    {
        return $this->belongsTo(CorporateCommunication::class, 'corporate_communications_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(CorporateCommunicationStatus::class, 'status_id');
    }
}
