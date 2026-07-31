<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GovernmentDataRequest extends Model
{
    protected $table = 'g_data';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_siction',
        'id_subsiction',
        'companies_groups_id',
        'branch_id',
        'subjec',
        'date',
        'Date_receipt',
        'Request_Receipt',
        'send_Section',
        'Data_delivery',
        'status',
        'fileToUpload',
        'filename',
        'Answer',
        'userid',
        'AnswerText',
        'AnswerDate',
        'Answer_userid',
        'create_at',
        'status_order',
        'becuse',
        'Date_status',
        'userid_status',
        'r1',
        'r2',
        'r3',
        'AnswerDates',
        'Answer_userids',
        'Data_delivery2',
        'date2',
        'userid_new',
        'Reminderـtime',
        'c',
        'count',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(GovernmentDataRequestEntity::class, 'id_siction');
    }

    public function dataType(): BelongsTo
    {
        return $this->belongsTo(GovernmentDataRequestDataType::class, 'id_subsiction');
    }

    public function receivingMethod(): BelongsTo
    {
        return $this->belongsTo(GovernmentDataRequestReceivingMethod::class, 'Request_Receipt');
    }

    public function currentStatus(): BelongsTo
    {
        return $this->belongsTo(GovernmentDataRequestStatus::class, 'status');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(GovernmentCircularSection::class, 'send_Section');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function mailFiles(): HasMany
    {
        return $this->hasMany(GovernmentDataRequestMailFile::class, 'id_data');
    }

    public function answerFiles(): HasMany
    {
        return $this->hasMany(GovernmentDataRequestAnswerFile::class, 'id_data');
    }

    public function timelineEntries(): HasMany
    {
        return $this->hasMany(GovernmentDataRequestTimeline::class, 'id_data');
    }

    public function views(): HasMany
    {
        return $this->hasMany(GovernmentDataRequestView::class, 'id_data', 'c');
    }

    public function displayNumber(): string
    {
        return '#'.(string) $this->id;
    }

    public function subject(): string
    {
        return trim((string) $this->subjec);
    }

    public function reminderAt(): ?string
    {
        $value = $this->getAttribute('Reminderـtime');

        return filled($value) ? (string) $value : null;
    }
}
