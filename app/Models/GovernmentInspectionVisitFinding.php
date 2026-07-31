<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentInspectionVisitFinding extends Model
{
    protected $table = 'government_inspection_visits_abuses_and_notes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'government_inspection_visits_id',
        'date',
        'type',
        'abuse_note_title',
        'reply',
        'uploaded_file',
        'replied_at',
        'replied_by',
        'created_by_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'replied_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(GovernmentInspectionVisit::class, 'government_inspection_visits_id');
    }

    public function isViolation(): bool
    {
        return (int) $this->type === 1;
    }

    public function isNote(): bool
    {
        return (int) $this->type === 2;
    }
}
