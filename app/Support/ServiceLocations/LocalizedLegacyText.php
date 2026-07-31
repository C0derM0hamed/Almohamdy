<?php

namespace App\Support\ServiceLocations;

class LocalizedLegacyText
{
    public static function dutyDays(?string $value): string
    {
        $value = self::normalize($value);

        if ($value === '') {
            return '—';
        }

        if (app()->getLocale() === 'ar') {
            return $value;
        }

        $map = __('service_locations.legacy.duty_days');

        if (isset($map[$value])) {
            return $map[$value];
        }

        foreach ($map as $arabic => $english) {
            if (str_contains($value, self::normalize($arabic))) {
                return $english;
            }
        }

        return $value;
    }

    public static function dutyTime(?string $value): string
    {
        $value = self::normalize($value);

        if ($value === '') {
            return '—';
        }

        if (app()->getLocale() === 'ar') {
            return $value;
        }

        return self::translateArabicScheduleText($value, __('service_locations.legacy.duty_time_phrases'));
    }

    public static function sectionName(?string $value): string
    {
        $value = self::normalize($value);

        if ($value === '') {
            return '—';
        }

        if (app()->getLocale() === 'ar') {
            return $value;
        }

        if (preg_match('/^العيادات الخارجية\s*(\d+)\s*$/u', $value, $matches) === 1) {
            return __('service_locations.opd_label', ['number' => $matches[1]]);
        }

        $map = __('service_locations.legacy.floor_services');

        if (isset($map[$value])) {
            return $map[$value];
        }

        foreach ($map as $arabic => $english) {
            if ($value === self::normalize($arabic)) {
                return $english;
            }
        }

        return $value;
    }

    public static function visitTime(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '—';
        }

        if (app()->getLocale() === 'ar') {
            return $value;
        }

        return self::translateArabicScheduleText($value, __('service_locations.legacy.visit_time_phrases'));
    }

    public static function floor(?string $nameEn, ?string $nameAr): string
    {
        $arabic = self::normalize($nameAr ?: $nameEn);
        $englishField = self::normalize($nameEn);

        if ($arabic === '' && $englishField === '') {
            return '—';
        }

        if (app()->getLocale() === 'ar') {
            return $arabic !== '' ? $arabic : $englishField;
        }

        $map = __('service_locations.legacy.floors');

        if ($arabic !== '' && isset($map[$arabic])) {
            return $map[$arabic];
        }

        if ($englishField !== '' && isset($map[$englishField])) {
            return $map[$englishField];
        }

        return $englishField !== '' ? $englishField : $arabic;
    }

    /**
     * @param  array<string, string>  $phraseMap
     */
    private static function translateArabicScheduleText(string $value, array $phraseMap): string
    {
        $lines = preg_split('/\R/u', $value) ?: [$value];
        $translated = [];

        foreach ($lines as $line) {
            $line = self::normalize($line);

            if ($line === '') {
                continue;
            }

            if (isset($phraseMap[$line])) {
                $translated[] = $phraseMap[$line];

                continue;
            }

            $matchedPhrase = false;

            foreach ($phraseMap as $arabic => $english) {
                $normalizedArabic = self::normalize($arabic);

                if ($line === $normalizedArabic || str_contains($line, $normalizedArabic)) {
                    $translated[] = $english;
                    $matchedPhrase = true;

                    break;
                }
            }

            if ($matchedPhrase) {
                continue;
            }

            $translated[] = self::translateArabicTimeLine($line);
        }

        return $translated !== [] ? implode("\n", $translated) : '—';
    }

    private static function translateArabicTimeLine(string $line): string
    {
        $result = preg_replace('/^من الساعة\s+/u', 'From ', $line) ?? $line;
        $result = preg_replace('/^من\s+/u', 'From ', $result) ?? $result;
        $result = preg_replace('/\s+(?:إلى|الى)\s+/u', ' to ', $result) ?? $result;
        $result = preg_replace('/\s+و\s+من\s+/u', ' and from ', $result) ?? $result;
        $result = preg_replace('/(\d{1,2}:\d{2})\s*ص/u', '$1 AM', $result) ?? $result;
        $result = preg_replace('/(\d{1,2}:\d{2})\s*م/u', '$1 PM', $result) ?? $result;
        $result = preg_replace('/(\d{1,2}:\d{2})ص/u', '$1 AM', $result) ?? $result;
        $result = preg_replace('/(\d{1,2}:\d{2})م/u', '$1 PM', $result) ?? $result;

        return trim($result);
    }

    private static function normalize(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }
}
