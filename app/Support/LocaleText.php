<?php

namespace App\Support;

class LocaleText
{
    /**
     * Return the value that should be shown in the active locale.
     *
     * A number of the legacy reference tables were imported with the Arabic
     * label copied into both name_ar and name_en.  Keep the database fields
     * independent, but transparently translate those legacy values when the
     * English UI is active.
     */
    public static function localizedValue(?string $arabic, ?string $english): string
    {
        return self::localizedField($arabic, $english);
    }

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
            $candidate = self::translateLegacyEnglish($english !== '' ? $english : $arabic)
                ?? ($english !== '' ? $english : $arabic);
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

        $candidate = $english !== '' ? $english : $arabic;
        $translated = self::translateLegacyEnglish($candidate);

        if ($translated !== null) {
            return $translated;
        }

        if ($candidate !== $arabic) {
            $translated = self::translateLegacyEnglish($arabic);

            if ($translated !== null) {
                return $translated;
            }
        }

        return $candidate;
    }

    private static function translateLegacyEnglish(string $value): ?string
    {
        $normalized = self::normalize($value);

        if ($normalized === '' || self::isMostlyLatin($normalized)) {
            return $normalized !== '' ? $normalized : null;
        }

        $overrides = [
            'تم إرسال الشكوى' => 'Complaint sent',
            'تم إرسال بريد تذكيرى اول' => 'First reminder email sent',
            'تم إرسال بريد تذكيرى ثاني' => 'Second reminder email sent',
            'تم تصعيد الشكوى لعدم رد القسم المعني' => 'Complaint escalated because the responsible department did not respond',
            'تم معالجة الشكوى' => 'Complaint processed',
            'تم إغلاق الشكوى' => 'Complaint closed',
            'الصباحية' => 'Morning shift',
            'المسائية' => 'Evening shift',
            'الليلية' => 'Night shift',
            'مصر' => 'Egypt',
            'السعودية' => 'Saudi Arabia',
            'الامارات' => 'United Arab Emirates',
            'الإمارات' => 'United Arab Emirates',
            'سوريا' => 'Syria',
            'الهند' => 'India',
            'تونس' => 'Tunisia',
            'الاردن' => 'Jordan',
            'الأردن' => 'Jordan',
            'مصري - بريطاني' => 'Egyptian - British',
        ];

        $catalog = (array) __('reference_data');
        $translated = $overrides[$normalized] ?? ($catalog[$normalized] ?? null);

        if (is_string($translated) && $translated !== '' && ! self::isMostlyArabic($translated)) {
            return $translated;
        }

        // Keep an English UI even for a newly-created legacy label that is
        // not yet in the catalogue.  Ar-PHP provides deterministic Arabic
        // transliteration and is already part of this application.
        try {
            $transliterator = new \ArPHP\I18N\Arabic();
            $transliterated = trim((string) $transliterator->ar2en($normalized));

            return $transliterated !== '' ? $transliterated : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function normalize(string $value): string
    {
        return trim((string) (preg_replace('/\s+/u', ' ', $value) ?? $value));
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
