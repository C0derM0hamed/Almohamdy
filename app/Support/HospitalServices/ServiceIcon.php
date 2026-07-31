<?php

namespace App\Support\HospitalServices;

use App\Models\ServicePackage;

final class ServiceIcon
{
    /**
     * Inline SVG markup for a dashboard section card.
     */
    public static function sectionSvg(int $sectionId): string
    {
        return self::sectionSvgs()[$sectionId] ?? self::sectionSvgs()['default'];
    }

    /**
     * Inline SVG markup for a service package card header.
     */
    public static function packageSvg(ServicePackage $package): string
    {
        $haystack = strtolower(trim(implode(' ', array_filter([
            (string) $package->code1,
            (string) $package->name_en,
            (string) $package->name_ar,
        ]))));

        foreach (self::packageRules() as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (self::matchesKeyword($haystack, $keyword)) {
                    return self::packageSvgs()[$rule['icon']] ?? self::packageSvgs()['default'];
                }
            }
        }

        $rotationIcons = [
            'stethoscope',
            'heart-pulse',
            'clipboard',
            'shield',
            'pregnancy',
            'pediatrics',
            'heart',
            'neurology',
        ];
        $index = abs((int) $package->id) % count($rotationIcons);

        return self::packageSvgs()[$rotationIcons[$index]];
    }

    private static function matchesKeyword(string $haystack, string $keyword): bool
    {
        $keyword = strtolower(trim($keyword));

        if ($keyword === '') {
            return false;
        }

        if (preg_match('/\p{Arabic}/u', $keyword)) {
            return str_contains($haystack, $keyword);
        }

        return (bool) preg_match('/\b'.preg_quote($keyword, '/').'\b/i', $haystack);
    }

    /**
     * @return array<int|string, string>
     */
    private static function sectionSvgs(): array
    {
        return [
            'default' => '<path d="M4 10h16v10H4z"/><path d="M9 10V6h6v4"/><path d="M12 3v3"/>',
            1 => '<path d="M5 20V9l7-5 7 5v11"/><path d="M9 20v-6h6v6"/><path d="M12 7v3"/><path d="M10.5 8.5h3"/>',
            2 => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4 7 17M17 7l1.4-1.4"/>',
            3 => '<path d="M9 3h6"/><path d="M10 3v4l-4 9a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l-4-9V3"/><path d="M8.5 14h7"/>',
            4 => '<circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><path d="M8 20l4-4 4 4"/><path d="M7 11l2 2M17 11l-2 2"/>',
            6 => '<path d="M4 12h16"/><path d="M6 12V8a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4"/><path d="M6 12v6h12v-6"/><path d="M10 18v2M14 18v2"/>',
            7 => '<path d="M4 10h16v9H4z"/><path d="M8 10V7h8v3"/><path d="M12 14v2"/>',
            8 => '<path d="M12 20a4 4 0 0 0 4-4v-2a4 4 0 0 0-8 0v2a4 4 0 0 0 4 4Z"/><path d="M9 10a3 3 0 1 1 6 0"/>',
            9 => '<rect x="4" y="6" width="16" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M8 6V4h8v2"/>',
            10 => '<path d="M12 3c4 4 4 8 0 12"/><path d="M8 9h8"/><path d="M7 15h10"/>',
            11 => '<path d="M8 12h2l1.5 2 3-4.5L15 9h2"/><path d="M4 17c1.5-1.5 3-1.5 4.5 0s3 1.5 4.5 0 3-1.5 4.5 0"/>',
            12 => '<path d="M6 20V8l6-4 6 4v12"/><path d="M10 20v-5h4v5"/><path d="M3 20h18"/>',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function packageSvgs(): array
    {
        return [
            'default' => '<path d="M6 4h8v2H6z"/><path d="M5 6h10v12H5z"/><path d="M9 10h6M12 7v6"/>',
            'stethoscope' => '<path d="M6 4h8v2H6z"/><path d="M5 6h10v8a3 3 0 0 0 6 0V9h2"/><circle cx="18" cy="7" r="2"/>',
            'heart-pulse' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/><path d="M3 13h3l2.5-3L11 14l2.5-4L17 13h4"/>',
            'clipboard' => '<rect x="7" y="4" width="10" height="16" rx="2"/><path d="M9 4h6v3H9z"/><path d="M9 12h6M9 16h4"/>',
            'shield' => '<path d="M12 3 19 6v6c0 4.5-3.2 7.4-7 9-3.8-1.6-7-4.5-7-9V6z"/><path d="m9.5 12 2 2 4-4"/>',
            'pregnancy' => '<circle cx="12" cy="8" r="4"/><path d="M7 20a5 5 0 0 1 10 0"/><circle cx="12" cy="13" r="2"/>',
            'pediatrics' => '<circle cx="12" cy="10" r="5"/><path d="M9 9h.01M15 9h.01M9.5 13.5c1 1 2.5 1 3.5 0"/>',
            'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/>',
            'neurology' => '<path d="M12 4c-2 0-3.5 1.5-3.5 3.5S10 11 12 11s3.5-1.5 3.5-3.5S14 4 12 4Z"/><path d="M8.5 12.5C7 14 6.5 16 7 18c1 2.5 3 4 5 4s4-1.5 5-4c.5-2-.5-4-2-5.5"/><path d="M12 11v2"/>',
            'lungs' => '<path d="M12 12c-2-5-4-7-7-7v12a4 4 0 0 0 7-2Z"/><path d="M12 12c2-5 4-7 7-7v12a4 4 0 0 1-7-2Z"/>',
            'bandaid' => '<rect x="5" y="8" width="14" height="8" rx="2"/><path d="M12 8v8M8 12h8"/>',
        ];
    }

    /**
     * @return list<array{keywords: list<string>, icon: string}>
     */
    private static function packageRules(): array
    {
        return [
            ['keywords' => ['ins', 'insurance', 'تأمين', 'جهات تأمين'], 'icon' => 'shield'],
            ['keywords' => ['rx', 'prescription', 'وصفة'], 'icon' => 'clipboard'],
            ['keywords' => ['obg', 'pregnan', 'حمل', 'نساء'], 'icon' => 'pregnancy'],
            ['keywords' => ['ped', 'pedia', 'أطفال', 'طفل'], 'icon' => 'pediatrics'],
            ['keywords' => ['card', 'cardio', 'قلب'], 'icon' => 'heart'],
            ['keywords' => ['neuro', 'مخ', 'أعصاب'], 'icon' => 'neurology'],
            ['keywords' => ['fu', 'follow', 'متابعة'], 'icon' => 'heart-pulse'],
            ['keywords' => ['pulmon', 'صدر', 'رئة'], 'icon' => 'lungs'],
            ['keywords' => ['med', 'checkup', 'فحص', 'زيارة'], 'icon' => 'stethoscope'],
        ];
    }
}
