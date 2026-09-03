<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GovAccountRequest extends Model
{
    protected $table = 'gov_account_requests';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'authority_submitted_at' => 'datetime', 'meta' => 'array', 'round' => 'integer'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id', 'hr_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(BranchDepartment::class, 'department_id');
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(GovAccountAuthority::class, 'authority_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(GovAccountService::class, 'service_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(GovAccountRole::class, 'role_id');
    }

    public function requestedRole(): BelongsTo
    {
        return $this->belongsTo(GovAccountRole::class, 'requested_role_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(GovAccount::class, 'account_id');
    }

    public function undertakings(): HasMany
    {
        return $this->hasMany(GovAccountUndertaking::class, 'request_id');
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(GovAccountTimeline::class, 'request_id')->orderBy('date');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(GovAccountAttachment::class, 'request_id');
    }
}
