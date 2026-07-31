<?php

namespace App\Support\DoctorsDirectory;

class DoctorCountBadge
{
    /**
     * Badge tone class for department doctor count.
     */
    public static function toneClass(int $count): string
    {
        if ($count === 0) {
            return 'hm-department-card__badge--muted';
        }

        if ($count <= 5) {
            return 'hm-department-card__badge--purple';
        }

        return 'hm-department-card__badge--green';
    }

    /**
     * Localized doctor count label (e.g. "1 Doctor", "5 Doctors", "٥ أطباء").
     */
    public static function label(int $count): string
    {
        if (app()->getLocale() === 'ar') {
            return self::arabicLabel($count);
        }

        return match ($count) {
            0 => 'No doctors available',
            1 => '1 Doctor',
            default => $count.' Doctors',
        };
    }

    private static function arabicLabel(int $count): string
    {
        return match ($count) {
            0 => 'لا يوجد أطباء متاحون',
            1 => 'طبيب واحد',
            default => self::toArabicDigits($count).' أطباء',
        };
    }

    private static function toArabicDigits(int $number): string
    {
        return strtr((string) $number, [
            '0' => '٠',
            '1' => '١',
            '2' => '٢',
            '3' => '٣',
            '4' => '٤',
            '5' => '٥',
            '6' => '٦',
            '7' => '٧',
            '8' => '٨',
            '9' => '٩',
        ]);
    }
}
