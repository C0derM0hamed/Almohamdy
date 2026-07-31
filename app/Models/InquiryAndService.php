<?php

namespace App\Models;

use App\Support\Inquiries\InquiryUserNameResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InquiryAndService extends Model
{
    protected $table = 'inquiries_and_services';

    protected $primaryKey = 'id';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'date',
        'branch_id',
        'job_title_sender',
        'enquirer',
        'inquired_section',
        'mobile',
        'inquiry_id',
        'inquiry_details',
        'created_by',
        'created_at',
        'companies_groups_id',
        'status',
        'job_title',
    ];

    public function inquiredSection(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'inquired_section');
    }

    public function inquiryType(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class, 'inquiry_id');
    }

    public function senderBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function currentStatus(): BelongsTo
    {
        return $this->belongsTo(InquiryAndServiceStatus::class, 'status');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(InquiryAndServiceReply::class, 'inquiries_and_services_id');
    }

    public function formattedDate(): string
    {
        $timestamp = (int) $this->date;

        return $timestamp > 0 ? Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s') : '—';
    }

    public function enquirerDisplayName(): string
    {
        $name = trim((string) $this->enquirer);

        return $name !== '' && $name !== '-' ? $name : '—';
    }

    public function creatorDisplayName(): string
    {
        return InquiryUserNameResolver::resolve((int) $this->created_by);
    }

    public function hasForm(): bool
    {
        return true;
    }

    public function formLabel(): string
    {
        if ($this->relationLoaded('inquiryType') && $this->inquiryType) {
            return $this->inquiryType->localizedName();
        }

        $details = trim((string) $this->inquiry_details);

        return $details !== '' ? $details : __('inquiries.form');
    }
}
