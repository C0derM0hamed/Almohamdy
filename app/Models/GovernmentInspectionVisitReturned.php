<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentInspectionVisitReturned extends Model
{
    protected $table = 'government_inspection_visits_returned';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'government_inspection_visits_id',
        'government_inspection_visits_abuses_and_notes_id',
        'reason',
        'required',
        'uploaded_file',
        'created_at',
        'created_by',
        'reply',
        'replied_at',
        'replied_by',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'replied_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(GovernmentInspectionVisit::class, 'government_inspection_visits_id');
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentInspectionVisitFinding::class,
            'government_inspection_visits_abuses_and_notes_id'
        );
    }
}
