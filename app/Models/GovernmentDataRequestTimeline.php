<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentDataRequestTimeline extends Model
{
    protected $table = 'g_timestatus';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'status',
        'userid',
        'id_data',
        'create_at',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(GovernmentDataRequest::class, 'id_data');
    }

    public function statusRecord(): BelongsTo
    {
        return $this->belongsTo(GovernmentDataRequestStatus::class, 'status');
    }
}
