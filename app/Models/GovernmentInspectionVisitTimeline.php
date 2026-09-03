<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentInspectionVisitTimeline extends Model
{
    protected $table = 'government_inspection_visits_timeline';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'government_inspection_visits_id',
        'status_id',
        'date',
        'notice',
        'created_by',
        'created_by_type',
        'branch_id',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(GovernmentInspectionVisit::class, 'government_inspection_visits_id');
    }

    public function statusRelation(): BelongsTo
    {
        return $this->belongsTo(GovernmentInspectionStatus::class, 'status_id');
    }
}
