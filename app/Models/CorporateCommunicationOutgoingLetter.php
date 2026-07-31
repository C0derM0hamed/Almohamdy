<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorporateCommunicationOutgoingLetter extends Model
{
    protected $table = 'corporate_communications_outgoing_letters';

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
        'letter_content',
        'government_circulars_receiving_mechanism_id',
        'sender_gender',
        'sender',
        'job_title',
        'type',
        'receiving_response_date',
        'created_by',
        'created_at',
        'companies_groups_id',
        'status',
        'replied_status',
        'sms_tocken',
        'document_status',
        'attachment_type',
        'registration_number',
        'year',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'datetime',
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

    public function currentStatus(): BelongsTo
    {
        return $this->belongsTo(CorporateCommunicationOutgoingLetterStatus::class, 'status');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(
            CorporateCommunicationOutgoingLetterAttachment::class,
            'corporate_communications_outgoing_letters_id'
        );
    }

    public function timelineEntries(): HasMany
    {
        return $this->hasMany(
            CorporateCommunicationOutgoingLetterTimeline::class,
            'corporate_communications_outgoing_letters_id'
        );
    }

    public function supplementaryLetters(): HasMany
    {
        return $this->hasMany(
            CorporateCommunicationOutgoingLetterSupplementary::class,
            'corporate_communications_outgoing_letters_id'
        );
    }

    public function displayNumber(): string
    {
        $year = (int) $this->year;
        $reg = (int) $this->registration_number;

        if ($year > 0 && $reg > 0) {
            return $year.'/'.$reg;
        }

        return '#'.(string) $this->id;
    }

    public function paddedRegistrationNumber(): string
    {
        return str_pad((string) (int) $this->registration_number, 4, '0', STR_PAD_LEFT);
    }

    public function defaultSupplementaryContent(): string
    {
        $ref = $this->paddedRegistrationNumber().'-'.(string) $this->year;
        $date = $this->issue_date?->format('Y/m/d') ?: '—';
        $subject = $this->subject() ?: '—';

        return "إشارةً إلى خطابنا رقم ({$ref}) وتاريخ ({$date}) المتعلق بـ ({$subject})، نفيدكم بأنه حتى تاريخه لم يردنا من جهتكم أي رد أو إفادة بخصوص ما تم الرفع عنه.\n"
            ."ونظرًا لأهمية الموضوع وارتباطه بـ (اذكر الأثر: سير العمل، الإجراءات النظامية، استكمال المتطلبات، إلخ)، نأمل من سعادتكم التكرم بمراجعة الموضوع وإفادتنا بما يلزم في أسرع وقت ممكن، ليتم استكمال الإجراءات وفقًا للأنظمة والتعليمات.";
    }

    public function subject(): string
    {
        return trim((string) $this->type);
    }

    public function senderGenderLabel(): string
    {
        return match ((string) $this->sender_gender) {
            '1' => __('outgoing_correspondence.fields.male'),
            '2' => __('outgoing_correspondence.fields.female'),
            default => '—',
        };
    }
}
