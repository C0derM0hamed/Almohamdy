<?php

namespace App\Support;

class LocaleText
{
    public static function isMostlyArabic(string $text): bool
    {
        $stripped = trim(strip_tags($text));

        if ($stripped === '') {
            return false;
        }

        $arabicCount = preg_match_all('/[\x{0600}-\x{06FF}]/u', $stripped);
        $latinCount = preg_match_all('/[A-Za-z]/', $stripped);

        if ($arabicCount === 0 && $latinCount === 0) {
            return false;
        }

        return $arabicCount >= $latinCount;
    }

    public static function isMostlyLatin(string $text): bool
    {
        $stripped = trim(strip_tags($text));

        if ($stripped === '') {
            return false;
        }

        $latinCount = preg_match_all('/[A-Za-z]/', $stripped);

        return $latinCount > 0 && ! self::isMostlyArabic($stripped);
    }

    public static function localizedHtml(?string $arabic, ?string $english): ?string
    {
        $arabic = trim((string) $arabic);
        $english = trim((string) $english);

        if (app()->getLocale() === 'ar') {
            $candidate = $arabic !== '' ? $arabic : $english;
        } elseif ($english !== '' && self::isMostlyLatin($english)) {
            $candidate = $english;
        } else {
            // English requested but no genuine English text — fall back to
            // whatever content exists so the page is not blank.
            $candidate = $english !== '' ? $english : $arabic;
        }

        if ($candidate === null || $candidate === '') {
            return null;
        }

        $clean = strip_tags($candidate, '<p><br><strong><em><b><i><ul><ol><li>');

        return trim($clean) !== '' ? $clean : null;
    }

    public static function localizedField(?string $arabic, ?string $english): string
    {
        $arabic = trim((string) $arabic);
        $english = trim((string) $english);

        if (app()->getLocale() === 'ar') {
            return $arabic !== '' ? $arabic : $english;
        }

        if ($english !== '' && self::isMostlyLatin($english)) {
            return $english;
        }

        $opdLabel = self::outpatientClinicLabel($arabic !== '' ? $arabic : $english);

        if ($opdLabel !== null) {
            return $opdLabel;
        }

        return $english !== '' ? $english : $arabic;
    }

    public static function outpatientClinicLabel(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (app()->getLocale() === 'ar') {
            return null;
        }

        if (preg_match('/العيادات\s+الخارجية\s*([0-9٠-٩]+)/u', $value, $matches) === 1) {
            return __('service_locations.opd_label', ['number' => self::normalizeDigits($matches[1])]);
        }

        if (preg_match('/O\.?\s*P\.?\s*D\.?\s*(\d+)/iu', $value, $matches) === 1) {
            return __('service_locations.opd_label', ['number' => $matches[1]]);
        }

        if (preg_match('/outpatient\s+clinic\s*(?:no\.?|#)?\s*(\d+)/iu', $value, $matches) === 1) {
            return __('service_locations.opd_label', ['number' => $matches[1]]);
        }

        return null;
    }

    private static function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
