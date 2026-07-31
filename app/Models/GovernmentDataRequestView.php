<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentDataRequestView extends Model
{
    protected $table = 'g_view';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'userid',
        'type',
        'id_data',
        'status',
        'created_at',
    ];

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(GovernmentCircularSectionAdministrator::class, 'userid');
    }

    public function hasBeenViewed(): bool
    {
        return (int) $this->status === 1;
    }
}
