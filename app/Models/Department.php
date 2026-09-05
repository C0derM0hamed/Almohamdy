<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A hospital department. The legacy database table is named `branches`, but
 * its rows are departments within a CompanyGroup (the actual hospital branch).
 */
class Department extends Model
{
    protected $table = 'branches';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function localizedName(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->name_ar ?: $this->name_en));
        }

        return trim((string) ($this->name_en ?: $this->name_ar));
    }
}
