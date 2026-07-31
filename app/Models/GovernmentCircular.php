<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GovernmentCircular extends Model
{
    protected $table = 'government_circulars';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'branch_id',
        'government_circulars_issuing_authority_id',
        'government_circulars_issuing_authority_classification_id',
        'issue_date',
        'received_date',
        'government_circulars_receiving_mechanism_id',
        'subject',
        'government_circulars_sections_id',
        'circulars_file',
        'created_by',
        'created_at',
        'companies_groups_id',
        'status',
        'sms_tocken',
        'attachment_type',
        'companies_groups_id_recipients',
        'notification_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'datetime',
            'received_date' => 'datetime',
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

    public function classification(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentCircularIssuingAuthorityClassification::class,
            'government_circulars_issuing_authority_classification_id'
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
        return $this->belongsTo(GovernmentCircularStatus::class, 'status');
    }

    public function notificationType(): BelongsTo
    {
        return $this->belongsTo(GovernmentCircularNotificationType::class, 'notification_type');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(GovernmentCircularAttachment::class, 'government_circulars_id');
    }

    public function receiptReports(): HasMany
    {
        return $this->hasMany(GovernmentCircularReceiptReport::class, 'government_circulars_id');
    }

    public function displayNumber(): string
    {
        return '#'.(string) $this->id;
    }

    public function primarySectionId(): int
    {
        $raw = trim((string) $this->government_circulars_sections_id);

        if ($raw === '') {
            return 0;
        }

        $parts = preg_split('/\s*,\s*/', $raw) ?: [];

        return (int) ($parts[0] ?? 0);
    }

    /**
     * @return list<int>
     */
    public function sectionIds(): array
    {
        $raw = trim((string) $this->government_circulars_sections_id);

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($id) => (int) $id,
            preg_split('/\s*,\s*/', $raw) ?: []
        )));
    }
}
