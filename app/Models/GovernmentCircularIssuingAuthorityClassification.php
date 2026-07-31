<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentCircularIssuingAuthorityClassification extends Model
{
    protected $table = 'government_circulars_issuing_authority_classification';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function authority(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentCircularIssuingAuthority::class,
            'government_circulars_issuing_authority_id'
        );
    }

    public function localizedName(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->name_ar ?: $this->name_en));
        }

        return trim((string) ($this->name_en ?: $this->name_ar));
    }
}
