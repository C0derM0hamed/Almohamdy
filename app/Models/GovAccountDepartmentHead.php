<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovAccountDepartmentHead extends Model
{
    protected $table = 'gov_account_department_heads';

    protected $fillable = ['companies_groups_id', 'department_id', 'user_id', 'publish'];

    protected function casts(): array
    {
        return ['publish' => 'boolean'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(BranchDepartment::class, 'department_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'hr_id');
    }
}
