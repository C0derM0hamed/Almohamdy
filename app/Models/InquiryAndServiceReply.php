<?php

namespace App\Models;

use App\Support\Inquiries\InquiryUserNameResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryAndServiceReply extends Model
{
    protected $table = 'inquiries_and_services_reply';

    protected $primaryKey = 'id';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inquiries_and_services_id',
        'inquiry_status_id',
        'branch_id',
        'inquired_section',
        'inquiry_id',
        'inquiry_details',
        'created_by',
        'created_at',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(InquiryAndServiceStatus::class, 'inquiry_status_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function creatorDisplayName(): string
    {
        return InquiryUserNameResolver::resolve((int) $this->created_by);
    }

    public function formattedCreatedAt(): string
    {
        $value = $this->created_at;

        if ($value === null || $value === '') {
            return '—';
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
