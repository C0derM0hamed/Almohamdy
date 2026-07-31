<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GovernmentInspectionVisit extends Model
{
    protected $table = 'government_inspection_visits';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'visit_number',
        'date',
        'branch_id',
        'government_circulars_issuing_authority_id',
        'visit_type',
        'visit_date',
        'reply_time',
        'government_circulars_sections_id',
        'users',
        'created_by',
        'created_at',
        'companies_groups_id',
        'status',
        'sms_tocken',
        'reply',
        'affidavit_document',
        'government_inspection_visits_types_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visit_date' => 'datetime',
            'reply_time' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentCircularIssuingAuthority::class,
            'government_circulars_issuing_authority_id'
        );
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentCircularSection::class,
            'government_circulars_sections_id'
        );
    }

    public function currentStatus(): BelongsTo
    {
        return $this->belongsTo(GovernmentInspectionStatus::class, 'status');
    }

    public function visitType(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentInspectionVisitType::class,
            'government_inspection_visits_types_id'
        );
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function visitNumberRecord(): BelongsTo
    {
        return $this->belongsTo(GovernmentInspectionVisitNumber::class, 'visit_number');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(GovernmentInspectionVisitFinding::class, 'government_inspection_visits_id');
    }

    public function timelineEntries(): HasMany
    {
        return $this->hasMany(GovernmentInspectionVisitTimeline::class, 'government_inspection_visits_id');
    }

    public function receiptReports(): HasMany
    {
        return $this->hasMany(GovernmentInspectionVisitReceiptReport::class, 'government_inspection_visits_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(GovernmentInspectionVisitAttachment::class, 'government_inspection_visits_id');
    }

    public function returnedItems(): HasMany
    {
        return $this->hasMany(GovernmentInspectionVisitReturned::class, 'government_inspection_visits_id');
    }

    public function replySubmissions(): HasMany
    {
        return $this->hasMany(GovernmentInspectionVisitReplySubmission::class, 'government_inspection_visits_id');
    }

    public function displayNumber(): string
    {
        return '#'.(string) $this->visit_number;
    }

    public function sectionIdValue(): int
    {
        return (int) $this->government_circulars_sections_id;
    }
}
