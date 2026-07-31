<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovernmentInspectionStatus extends Model
{
    protected $table = 'government_inspection_status';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function localizedName(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->name_ar ?: $this->name_en));
        }

        return trim((string) ($this->name_en ?: $this->name_ar));
    }

    public function badgeColor(): string
    {
        $color = trim((string) $this->info);

        return $color !== '' ? $color : match ((int) $this->id) {
            1 => '#1a4f86',
            2 => '#15803d',
            3 => '#b45309',
            4 => '#7c3aed',
            5 => '#0f766e',
            6 => '#0369a1',
            7 => '#be123c',
            default => '#64748b',
        };
    }
}
