<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseEscalationGroupMember extends Model
{
    protected $table = 'license_escalation_group_members';

    /** @var list<string> */
    protected $fillable = ['group_id', 'user_id'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(LicenseEscalationGroup::class, 'group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'hr_id');
    }
}
