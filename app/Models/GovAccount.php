<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GovAccount extends Model
{
    protected $table = 'gov_accounts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['account_created_at' => 'date', 'suspended_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id', 'hr_id');
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

    public function role(): BelongsTo
    {
        return $this->belongsTo(GovAccountRole::class, 'role_id');
    }

    public function sourceRequest(): BelongsTo
    {
        return $this->belongsTo(GovAccountRequest::class, 'created_from_request_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(GovAccountRequest::class, 'account_id');
    }
}
