<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorporateCommunicationStatus extends Model
{
    protected $table = 'corporate_communications_status';

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
            2 => '#b45309',
            3 => '#15803d',
            4 => '#0f766e',
            5 => '#be123c',
            6 => '#0369a1',
            7 => '#7c3aed',
            8 => '#15803d',
            9 => '#c2410c',
            10 => '#0369a1',
            11, 12 => '#7c3aed',
            default => '#64748b',
        };
    }
}
