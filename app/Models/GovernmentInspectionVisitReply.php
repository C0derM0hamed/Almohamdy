<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentInspectionVisitReply extends Model
{
    protected $table = 'government_inspection_visits_reply';

    public $timestamps = false;

    protected $fillable = [
        'government_inspection_visits_id',
        'reply',
        'created_by',
        'created_at',
        'created_by_type',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(GovernmentInspectionVisit::class, 'government_inspection_visits_id');
    }
}
