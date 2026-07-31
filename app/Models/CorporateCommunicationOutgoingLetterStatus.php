<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorporateCommunicationOutgoingLetterStatus extends Model
{
    protected $table = 'corporate_communications_outgoing_letters_status';

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
        return match ((int) $this->id) {
            1 => '#1a4f86',
            2 => '#0369a1',
            3 => '#0f766e',
            4 => '#15803d',
            5 => '#be123c',
            6 => '#b45309',
            7 => '#7c3aed',
            8 => '#15803d',
            9 => '#c2410c',
            10 => '#0369a1',
            11 => '#15803d',
            13 => '#64748b',
            default => '#64748b',
        };
    }
}
