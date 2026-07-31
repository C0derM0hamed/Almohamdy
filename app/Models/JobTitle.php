<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobTitle extends Model
{
    protected $table = 'job_titles';

    public $timestamps = false;

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar'
            ? trim((string) ($this->name_ar ?: $this->name_en))
            : trim((string) ($this->name_en ?: $this->name_ar));
    }
}
