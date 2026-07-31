<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovernmentDataRequestStatus extends Model
{
    protected $table = 'g_status';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function localizedName(): string
    {
        return trim((string) $this->name) ?: '#'.$this->id;
    }

    public function badgeColor(): string
    {
        return match ((int) $this->id) {
            1 => '#7c3aed',
            2 => '#be123c',
            4 => '#0369a1',
            5 => '#0f766e',
            6 => '#1a4f86',
            7 => '#b45309',
            8 => '#15803d',
            9 => '#c2410c',
            default => '#64748b',
        };
    }
}
