<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmergencyFollowUp extends Model
{
    protected $table = 'emergency_follow_up';

    public $timestamps = false;

    protected $fillable = [
        'date', 'branch_id', 'file_number', 'notice', 'description',
        'notice_type', 'action', 'status', 'created_by', 'updated_by', 'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'branch_id' => 'integer',
        'file_number' => 'integer',
        'notice' => 'integer',
        'notice_type' => 'integer',
        'status' => 'integer',
        'created_by' => 'integer',
    ];

    public function noticeType(): BelongsTo
    {
        return $this->belongsTo(EmergencyNoticeType::class, 'notice');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'hr_id');
    }

    public function notices(): HasMany
    {
        return $this->hasMany(EmergencyFollowUpNotice::class, 'emergency_follow_up_id')->latest('id');
    }

    public function latestNotice(): HasOne
    {
        return $this->hasOne(EmergencyFollowUpNotice::class, 'emergency_follow_up_id')->latestOfMany('id');
    }
}
