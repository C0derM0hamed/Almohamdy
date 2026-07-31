<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentInspectionVisitReplySubmission extends Model
{
    protected $table = 'government_inspection_visits_reply_submission';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'government_inspection_visits_id',
        'date',
        'attachment_type',
        'file_name',
        'created_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(GovernmentInspectionVisit::class, 'government_inspection_visits_id');
    }
}
