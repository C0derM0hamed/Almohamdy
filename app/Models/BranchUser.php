<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BranchUser extends Model
{
    protected $table = 'branch_users';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function vacations(): HasMany
    {
        return $this->hasMany(EmployeeVacation::class, 'emp_id');
    }

    public function displayName(): string
    {
        return trim((string) $this->br_user_full_name);
    }
}
