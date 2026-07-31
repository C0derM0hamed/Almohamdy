<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorporateCommunication extends Model
{
    protected $table = 'corporate_communications';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'branch_id',
        'sectors_id',
        'government_circulars_issuing_authority_id',
        'corporate_communications_senderTitle_id',
        'issue_date',
        'received_date',
        'government_circulars_receiving_mechanism_id',
        'sender_gender',
        'sender',
        'job_title',
        'type',
        'government_circulars_sections_id',
        'receiving_response_date',
        'created_by',
        'created_at',
        'companies_groups_id',
        'status',
        'replied_status',
        'sms_tocken',
        'document_status',
        'attachment_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'datetime',
            'received_date' => 'datetime',
            'receiving_response_date' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(CorporateCommunicationSector::class, 'sectors_id');
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentCircularIssuingAuthority::class,
            'government_circulars_issuing_authority_id'
        );
    }

    public function senderTitle(): BelongsTo
    {
        return $this->belongsTo(
            CorporateCommunicationSenderTitle::class,
            'corporate_communications_senderTitle_id'
        );
    }

    public function receivingMechanism(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentCircularReceivingMechanism::class,
            'government_circulars_receiving_mechanism_id'
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
        return $this->belongsTo(CorporateCommunicationStatus::class, 'status');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CorporateCommunicationAttachment::class, 'corporate_communications_id');
    }

    public function receiptReports(): HasMany
    {
        return $this->hasMany(CorporateCommunicationReceiptReport::class, 'corporate_communications_id');
    }

    public function timelineEntries(): HasMany
    {
        return $this->hasMany(CorporateCommunicationTimeline::class, 'corporate_communications_id');
    }

    public function displayNumber(): string
    {
        return '#'.(string) $this->id;
    }

    public function subject(): string
    {
        return trim((string) $this->type);
    }

    public function senderGenderLabel(): string
    {
        return match ((string) $this->sender_gender) {
            '1' => __('correspondence.fields.male'),
            '2' => __('correspondence.fields.female'),
            default => '—',
        };
    }
}
