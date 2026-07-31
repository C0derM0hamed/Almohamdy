<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayOfferWorkingDay extends Model
{
    protected $table = 'holidays_offers_clinicians_working_days';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    public function holidayOffer(): BelongsTo
    {
        return $this->belongsTo(HolidayOffer::class, 'holidays_offers_id');
    }

    public function localizedDay(): string
    {
        $key = 'doctors_directory.weekdays.'.(int) $this->day;

        return __($key) !== $key ? __($key) : (string) $this->day;
    }

    public function formattedTimeFrom(): string
    {
        return $this->formatTime((string) $this->time_from);
    }

    public function formattedTimeTo(): string
    {
        return $this->formatTime((string) $this->time_to);
    }

    private function formatTime(string $time): string
    {
        $time = trim($time);

        if ($time === '') {
            return '';
        }

        return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
    }
}
