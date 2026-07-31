<?php

namespace App\Support\DoctorsDirectory;

/**
 * Legacy weekday index used in holidays_offers_clinicians_working_days.day
 * (1 = Saturday … 7 = Friday).
 */
final class LegacyWeekday
{
    public static function today(): int
    {
        return ((int) date('w') + 1) % 7 + 1;
    }
}
