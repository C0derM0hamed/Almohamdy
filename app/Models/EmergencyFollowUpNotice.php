<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyFollowUpNotice extends Model
{
    protected $table = 'emergency_follow_up_notice';

    public $timestamps = false;

    protected $fillable = ['emergency_follow_up_id', 'notice', 'created_at', 'created_by'];

    protected $casts = ['emergency_follow_up_id' => 'integer', 'created_by' => 'integer'];

    public function followUp(): BelongsTo
    {
        return $this->belongsTo(EmergencyFollowUp::class, 'emergency_follow_up_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'hr_id');
    }
}
