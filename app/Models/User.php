<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Model
{
    protected $table = 'ra_users';

    protected $primaryKey = 'hr_id';

    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'hr_first_name',
        'hr_last_name',
        'hr_email_address',
        'hr_username',
        'hr_password',
        'hr_user_level',
        'branch_id',
        'companies_groups_id',
        'groupid',
        'mobile',
        'activated',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'hr_password',
    ];

    public function displayName(): string
    {
        $name = trim((string) $this->hr_first_name.' '.(string) $this->hr_last_name);

        if ($name !== '') {
            return $name;
        }

        return trim((string) $this->hr_username) ?: '#'.$this->hr_id;
    }

    /** @param \Illuminate\Database\Eloquent\Builder<self> $query */
    public function scopeActivated($query)
    {
        // ra_users.activated is enum('0','1') — never compare with integer 1 (matches wrong rows).
        return $query->where('activated', '1');
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'job_title');
    }
}
