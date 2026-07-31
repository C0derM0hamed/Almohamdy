<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentInspectionVisitReceiptReport extends Model
{
    protected $table = 'government_inspection_visits_receipt_reports';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'government_inspection_visits_id',
        'government_circulars_sections_administrators_id',
        'created_at',
        'seen_by_sms_at',
        'seen_by_email_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'seen_by_sms_at' => 'datetime',
            'seen_by_email_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(GovernmentInspectionVisit::class, 'government_inspection_visits_id');
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentCircularSectionAdministrator::class,
            'government_circulars_sections_administrators_id'
        );
    }

    public function hasBeenViewed(): bool
    {
        return $this->seen_by_email_at !== null || $this->seen_by_sms_at !== null;
    }
}
