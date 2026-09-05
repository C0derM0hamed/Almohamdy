<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchDepartment extends Model
{
    protected $table = 'branches_departments';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function localizedName(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->name_ar ?: $this->name_en));
        }

        return trim((string) ($this->name_en ?: $this->name_ar));
    }

    public function parentDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'branch_id');
    }

    public function hierarchyLabel(): string
    {
        $unit = $this->localizedName();
        $parent = $this->relationLoaded('parentDepartment')
            ? $this->parentDepartment?->localizedName()
            : null;

        if (! $parent || mb_strtolower(trim($parent)) === mb_strtolower(trim($unit))) {
            return $unit;
        }

        return $parent.' — '.$unit;
    }
}
