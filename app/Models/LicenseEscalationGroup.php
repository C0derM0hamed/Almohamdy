<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseEscalationGroup extends Model
{
    protected $table = 'license_escalation_groups';

    /** @var list<string> */
    protected $fillable = ['companies_groups_id', 'name', 'publish'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['publish' => 'boolean'];
    }

    public function members(): HasMany
    {
        return $this->hasMany(LicenseEscalationGroupMember::class, 'group_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'license_escalation_group_members',
            'group_id',
            'user_id',
            'id',
            'hr_id',
        )->withTimestamps();
    }

    public function localizedName(): string
    {
        return trim((string) $this->name);
    }
}
