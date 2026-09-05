<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GovAccountNotice extends Model
{
    protected $table = 'gov_account_notices';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['targeting' => 'array', 'event_date' => 'date', 'sent_at' => 'datetime', 'publish' => 'boolean'];
    }

    public function hospitalBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class, 'companies_groups_id');
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(GovAccountAuthority::class, 'authority_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(GovAccountService::class, 'service_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(GovAccountNoticeRecipient::class, 'notice_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(GovAccountAttachment::class, 'notice_id');
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(GovAccountTimeline::class, 'notice_id')->orderBy('date');
    }
}
