<?php

namespace App\Support\HospitalServices;

final class SectionNavPresentation
{
    /**
     * @var array<int, string>
     */
    private const ICONS = [
        1 => 'bi-hospital',
        2 => 'bi-radioactive',
        3 => 'bi-flask',
        4 => 'bi-person-arms-up',
        6 => 'bi-hospital-fill',
        7 => 'bi-door-closed',
        8 => 'bi-heart',
        9 => 'bi-camera-reels',
        10 => 'bi-droplet-half',
        11 => 'bi-handshake',
        12 => 'bi-buildings',
    ];

    /**
     * @var array<int, array{en: string, ar: string}>
     */
    private const PAGE_SUBTITLES = [
        1 => [
            'en' => 'Browse and manage outpatient clinic services and packages.',
            'ar' => 'استعرض وأدر خدمات وباقات العيادات الخارجية.',
        ],
        2 => [
            'en' => 'Browse and manage radiology services and packages.',
            'ar' => 'استعرض وأدر خدمات وباقات الأشعة.',
        ],
        3 => [
            'en' => 'Browse and manage laboratory services and packages.',
            'ar' => 'استعرض وأدر خدمات وباقات المختبر.',
        ],
        4 => [
            'en' => 'Browse and manage physiotherapy services and packages.',
            'ar' => 'استعرض وأدر خدمات وباقات العلاج الطبيعي.',
        ],
        6 => [
            'en' => 'Browse and manage inpatient services and packages.',
            'ar' => 'استعرض وأدر خدمات وباقات التنويم.',
        ],
        7 => [
            'en' => 'Browse and manage private room options and packages.',
            'ar' => 'استعرض وأدر خيارات الغرف الخاصة والباقات.',
        ],
        8 => [
            'en' => 'Browse and manage infertility and reproduction services.',
            'ar' => 'استعرض وأدر خدمات العقم والإنجاب.',
        ],
        9 => [
            'en' => 'Browse and manage endoscopy unit services.',
            'ar' => 'استعرض وأدر خدمات وحدة المناظير.',
        ],
        10 => [
            'en' => 'Browse and manage dialysis unit services.',
            'ar' => 'استعرض وأدر خدمات وحدة غسيل الكلى.',
        ],
        11 => [
            'en' => 'Browse partners, discounts, and company agreements.',
            'ar' => 'استعرض الشركاء والخصومات واتفاقيات الشركات.',
        ],
        12 => [
            'en' => 'Browse contracted medical centers and network services.',
            'ar' => 'استعرض المراكز الطبية المتعاقدة وخدمات الشبكة.',
        ],
    ];

    /**
     * @var array<int, array{en: string, ar: string}>
     */
    private const DESCRIPTIONS = [
        1 => [
            'en' => 'Book and manage outpatient clinic services.',
            'ar' => 'حجز وإدارة خدمات العيادات الخارجية.',
        ],
        2 => [
            'en' => 'Imaging services and radiology appointments.',
            'ar' => 'خدمات التصوير ومواعيد الأشعة.',
        ],
        3 => [
            'en' => 'Lab tests and diagnostic services.',
            'ar' => 'الفحوصات المخبرية والخدمات التشخيصية.',
        ],
        4 => [
            'en' => 'Rehabilitation and physiotherapy services.',
            'ar' => 'خدمات التأهيل والعلاج الطبيعي.',
        ],
        6 => [
            'en' => 'Inpatient admission and hospital services.',
            'ar' => 'خدمات التنويم والرعاية داخل المستشفى.',
        ],
        7 => [
            'en' => 'Private rooms and suite options.',
            'ar' => 'الغرف الخاصة وخيارات الأجنحة.',
        ],
        8 => [
            'en' => 'Infertility and reproduction services.',
            'ar' => 'خدمات العقم والإنجاب.',
        ],
        9 => [
            'en' => 'Endoscopy unit services.',
            'ar' => 'خدمات وحدة المناظير.',
        ],
        10 => [
            'en' => 'Dialysis unit services.',
            'ar' => 'خدمات وحدة غسيل الكلى.',
        ],
        11 => [
            'en' => 'Explore partners and exclusive discounts.',
            'ar' => 'استكشف الشركاء والخصومات الحصرية.',
        ],
        12 => [
            'en' => 'View contracted medical centers and networks.',
            'ar' => 'عرض المراكز الطبية المتعاقدة والشبكات.',
        ],
    ];

    /**
     * @var list<string>
     */
    private const PACKAGE_ICONS = [
        'bi-stethoscope',
        'bi-heart-pulse',
        'bi-clipboard2-pulse',
        'bi-bandaid',
        'bi-activity',
        'bi-lungs',
    ];

    public static function iconFor(int $sectionId): string
    {
        return self::ICONS[$sectionId] ?? 'bi-grid';
    }

    public static function figmaCardIcon(int $sectionId): string
    {
        $map = [
            1 => 'images/figma/services/card-hospital.svg',
            2 => 'images/figma/services/card-scan.svg',
            3 => 'images/figma/services/card-flask.svg',
            4 => 'images/figma/services/card-physio.svg',
            6 => 'images/figma/services/card-hospital.svg',
            7 => 'images/figma/services/card-buildings.svg',
            8 => 'images/figma/services/card-hospital.svg',
            9 => 'images/figma/services/card-scan.svg',
            10 => 'images/figma/services/card-flask.svg',
            11 => 'images/figma/services/card-percent.svg',
            12 => 'images/figma/services/card-buildings.svg',
        ];

        return asset($map[$sectionId] ?? 'images/figma/services/card-hospital.svg');
    }

    public static function figmaHeroIcon(int $sectionId): string
    {
        if ($sectionId === 11) {
            return asset('images/figma/services/hero-percent.svg');
        }

        if (in_array($sectionId, [12, 7], true)) {
            return asset('images/figma/services/hero-buildings.svg');
        }

        return asset('images/figma/services/hero.svg');
    }

    public static function descriptionFor(int $sectionId): string
    {
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $description = self::DESCRIPTIONS[$sectionId][$locale] ?? null;

        if ($description !== null) {
            return $description;
        }

        return __('hospital_services.section_default_description');
    }

    public static function pageSubtitleFor(int $sectionId): string
    {
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $subtitle = self::PAGE_SUBTITLES[$sectionId][$locale] ?? null;

        if ($subtitle !== null) {
            return $subtitle;
        }

        return __('hospital_services.section_page_subtitle');
    }

    public static function packageIconFor(int $packageId): string
    {
        $icons = self::PACKAGE_ICONS;
        $index = abs($packageId) % count($icons);

        return $icons[$index];
    }
}
