<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolidayOffer extends Model
{
    protected $table = 'holidays_offers';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    public function clinician(): BelongsTo
    {
        return $this->belongsTo(Clinician::class, 'clinicians_id');
    }

    public function workingDays(): HasMany
    {
        return $this->hasMany(HolidayOfferWorkingDay::class, 'holidays_offers_id');
    }
}
