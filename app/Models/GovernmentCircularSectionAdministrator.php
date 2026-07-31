<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentCircularSectionAdministrator extends Model
{
    protected $table = 'government_circulars_sections_administrators';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function section(): BelongsTo
    {
        return $this->belongsTo(GovernmentCircularSection::class, 'government_circulars_sections_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentCircularSectionAdministratorType::class,
            'government_circulars_sections_administrators_types_id'
        );
    }

    public function displayName(): string
    {
        return trim((string) $this->administrator) ?: '#'.$this->id;
    }
}
