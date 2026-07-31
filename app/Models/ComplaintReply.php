<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintReply extends Model
{
    protected $table = 'complaints_reply';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'complaints_id', 'complaint_status_id', 'defendant', 'defendant_job',
        'file_number', 'section_id', 'details', 'date_sent', 'date_reply',
        'created_by', 'created_at', 'file_name',
    ];

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaints_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ComplaintStatus::class, 'complaint_status_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'hr_id');
    }

    public function creatorDisplayName(): string
    {
        if ($this->relationLoaded('creator') && $this->creator) {
            return trim($this->creator->hr_first_name.' '.$this->creator->hr_last_name);
        }

        return '#'.$this->created_by;
    }

    public function formattedCreatedAt(): string
    {
        if ($this->created_at === null) {
            return '—';
        }

        return Carbon::parse($this->created_at)->format('Y-m-d H:i');
    }
}
