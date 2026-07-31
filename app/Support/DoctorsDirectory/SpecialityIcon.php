<?php

namespace App\Support\DoctorsDirectory;

use App\Models\OutpatientClinicSection;
use App\Models\Speciality;

class SpecialityIcon
{
    /**
     * Bootstrap Icons class for a medical speciality.
     */
    public static function for(Speciality $speciality): string
    {
        return self::resolve(
            trim((string) $speciality->subject_en).' '.trim((string) $speciality->subject_ar)
        );
    }

    /**
     * Icon for a department section (clinic name + parent speciality).
     */
    public static function forDepartment(OutpatientClinicSection $section, Speciality $speciality): string
    {
        $clinicEn = $section->outpatientClinic?->name_en ?? '';
        $clinicAr = $section->outpatientClinic?->name_ar ?? '';

        return self::resolve(
            trim($clinicEn).' '.trim($clinicAr).' '.trim((string) $speciality->subject_en).' '.trim((string) $speciality->subject_ar)
        );
    }

    /**
     * Inline SVG inner markup (paths) for a speciality, matching the
     * redesign reference icon set. Returns the inner <path>/<circle>
     * elements to be wrapped in <svg viewBox="0 0 24 24">.
     */
    public static function svgFor(Speciality $speciality): string
    {
        return self::resolveSvg(
            trim((string) $speciality->subject_en).' '.trim((string) $speciality->subject_ar)
        );
    }

    private static function resolve(string $text): string
    {
        $haystack = strtolower($text);

        foreach (self::rules() as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (self::matchesKeyword($haystack, $keyword)) {
                    return $rule['icon'];
                }
            }
        }

        return 'bi-heart-pulse';
    }

    private static function resolveSvg(string $text): string
    {
        $haystack = strtolower($text);
        $icons = self::svgIcons();

        foreach (self::rules() as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (self::matchesKeyword($haystack, $keyword) && isset($icons[$rule['icon']])) {
                    return $icons[$rule['icon']];
                }
            }
        }

        return $icons['default'];
    }

    /**
     * Map of icon keys to inline SVG inner markup taken from the
     * design reference (stroke-based, 24x24 viewBox).
     *
     * @return array<string, string>
     */
    private static function svgIcons(): array
    {
        return [
            'default' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/>',
            // Internal medicine (stethoscope)
            'bi-hospital' => '<path d="M6 3v7a6 6 0 0 0 12 0V3"/><path d="M6 3H4M18 3h2M12 16v5M12 21h4"/>',
            'bi-thermometer-half' => '<path d="M6 3v7a6 6 0 0 0 12 0V3"/><path d="M6 3H4M18 3h2M12 16v5M12 21h4"/>',
            // Gastroenterology
            'bi-prescription2' => '<path d="M6 4c5 2 8 6 8 11a5 5 0 0 1-5 5H6"/><path d="M14 4c3 2 5 5 5 9a7 7 0 0 1-7 7"/>',
            // Rheumatology (spine)
            'bi-bandaid-fill' => '<path d="M12 3v18M8 7h8M7 12h10M9 17h6"/>',
            'bi-bandaid' => '<path d="M12 3v18M8 7h8M7 12h10M9 17h6"/>',
            // Pulmonology (lungs)
            'bi-lungs-fill' => '<path d="M12 12c-2-5-4-7-7-7v12a4 4 0 0 0 7-2Z"/><path d="M12 12c2-5 4-7 7-7v12a4 4 0 0 1-7-2Z"/>',
            // Hematology (blood drop)
            'bi-droplet-half' => '<path d="M12 2s7 8 7 13a7 7 0 0 1-14 0C5 10 12 2 12 2Z"/>',
            // Cardiology (heart)
            'bi-heart-pulse' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/>',
            'bi-heart-pulse-fill' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/>',
            // Orthopaedic (bone)
            'bi-cast' => '<path d="m14 5 5 5-9 9-5-5Z"/><path d="m16 3 5 5M3 16l5 5"/>',
            // General surgery / surgery (person)
            'bi-scissors' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            // Neurology (brain)
            'bi-cpu-fill' => '<path d="M9 5a3 3 0 0 1 6 0v14a3 3 0 0 1-6 0V5Z"/><path d="M6 10h12M6 14h12"/>',
            // Urology / nephrology (kidney)
            'bi-droplet' => '<path d="M8 4c-3 1-5 4-5 8s2 7 5 8c2-2 2-5 0-8 2-3 2-6 0-8Z"/><path d="M16 4c3 1 5 4 5 8s-2 7-5 8c-2-2-2-5 0-8-2-3-2-6 0-8Z"/>',
            // Dermatology
            'bi-moisture' => '<path d="M7 20h10M8 16h8M12 4v12M16 8l-4-4-4 4"/>',
            // Dental
            'bi-emoji-smile-fill' => '<path d="M12 3c-4 0-7 3-7 7 0 6 3 11 5 11 1.5 0 1-5 2-5s.5 5 2 5c2 0 5-5 5-11 0-4-3-7-7-7Z"/>',
            // Ophthalmology (eye)
            'bi-eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
            // ENT (ear)
            'bi-ear-fill' => '<path d="M8 8a4 4 0 1 1 7 2.6c-1.2 1-1 2.4-1.5 3.4-.6 1.2-1.7 2-3 2"/><path d="M12 20h.01"/>',
            // Pediatrics (baby face)
            'bi-balloon-heart' => '<circle cx="12" cy="12" r="8"/><path d="M9 10h.01M15 10h.01M9 15c1.5 1 4.5 1 6 0"/>',
            // Psychiatry
            'bi-emoji-neutral' => '<circle cx="12" cy="12" r="9"/><path d="M8 10h.01M16 10h.01M8 15h8"/>',
            // Oncology
            'bi-virus' => '<circle cx="12" cy="12" r="5"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
            // Gynaecology
            'bi-gender-female' => '<circle cx="12" cy="9" r="5"/><path d="M12 14v7M9 18h6"/>',
            // Vascular
            'bi-activity' => '<path d="M3 12h4l3 8 4-16 3 8h4"/>',
            // Plastic surgery
            'bi-stars' => '<path d="m12 2 3.1 6.3 6.9 1-5 4.8 1.2 6.9L12 17.8 5.8 21 7 14.1 2 9.3l6.9-1Z"/>',
            // Anaesthesia (pill)
            'bi-capsule-pill' => '<rect x="3" y="8" width="18" height="8" rx="4"/><path d="M12 8v8"/>',
            // Outpatient (door)
            'bi-door-open' => '<path d="M13 4v16M5 4h8v16H5zM13 12h6"/>',
        ];
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
     * @return list<array{keywords: list<string>, icon: string}>
     */
    private static function rules(): array
    {
        return [
            ['keywords' => ['cardio-pedia', 'cardiology-pedia', 'قلب أطفال'], 'icon' => 'bi-heart-pulse-fill'],
            ['keywords' => ['cardiology', 'cardio', 'قلب'], 'icon' => 'bi-heart-pulse'],
            ['keywords' => ['neurosurg', 'جراحة المخ'], 'icon' => 'bi-scissors'],
            ['keywords' => ['neurology', 'neurolog', 'مخ وأعصاب', 'أعصاب'], 'icon' => 'bi-cpu-fill'],
            ['keywords' => ['neuro', 'مخ'], 'icon' => 'bi-cpu-fill'],
            ['keywords' => ['pulmonology', 'pulmon', 'respir', 'صدر', 'تنفس', 'رئة'], 'icon' => 'bi-lungs-fill'],
            ['keywords' => ['orthopedics', 'orthopedic', 'orthopaedic', 'orthop', 'عظام'], 'icon' => 'bi-cast'],
            ['keywords' => ['dermatology', 'dermat', 'جلدية', 'جلد'], 'icon' => 'bi-moisture'],
            ['keywords' => ['dental', 'dentistry', 'odont', 'أسنان', 'اسنان'], 'icon' => 'bi-emoji-smile-fill'],
            ['keywords' => ['otorhinolaryngology', 'otolaryngology', 'أنف وأذن', 'أنف', 'أذن', 'حنجرة', 'ent'], 'icon' => 'bi-ear-fill'],
            ['keywords' => ['anaesth', 'anesthes', 'تخدير'], 'icon' => 'bi-capsule-pill'],
            ['keywords' => ['pain', 'ألم'], 'icon' => 'bi-bandaid'],
            ['keywords' => ['bariatric', 'سمنة'], 'icon' => 'bi-graph-down-arrow'],
            ['keywords' => ['circumcision', 'ختان'], 'icon' => 'bi-scissors'],
            ['keywords' => ['gastro', 'جهاز هضم', 'معدة'], 'icon' => 'bi-prescription2'],
            ['keywords' => ['rheumat', 'رومات'], 'icon' => 'bi-bandaid-fill'],
            ['keywords' => ['internal', 'باطن', 'غدد'], 'icon' => 'bi-hospital'],
            ['keywords' => ['hemato', 'أمراض الدم'], 'icon' => 'bi-droplet-half'],
            ['keywords' => ['vascular', 'أوعية', 'اشعة تداخل'], 'icon' => 'bi-activity'],
            ['keywords' => ['plastic', 'تجميل'], 'icon' => 'bi-stars'],
            ['keywords' => ['general surg', 'جراحة العامة', 'جراحة عامة'], 'icon' => 'bi-scissors'],
            ['keywords' => ['urol', 'مسالك'], 'icon' => 'bi-droplet'],
            ['keywords' => ['ophth', 'عيون'], 'icon' => 'bi-eye'],
            ['keywords' => ['psych', 'نفس'], 'icon' => 'bi-emoji-neutral'],
            ['keywords' => ['obstet', 'gyne', 'gynec', 'نساء', 'ولادة'], 'icon' => 'bi-gender-female'],
            ['keywords' => ['pediat', 'أطفال', 'pedia'], 'icon' => 'bi-balloon-heart'],
            ['keywords' => ['oncol', 'أورام'], 'icon' => 'bi-virus'],
            ['keywords' => ['nephro', 'كلى'], 'icon' => 'bi-droplet'],
            ['keywords' => ['endocrin'], 'icon' => 'bi-thermometer-half'],
            ['keywords' => ['surgery', 'surg', 'جراح'], 'icon' => 'bi-scissors'],
            ['keywords' => ['عيادات خارجية', 'outpatient'], 'icon' => 'bi-door-open'],
        ];
    }
}
