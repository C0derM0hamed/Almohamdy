<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinician extends Model
{
    protected $table = 'clinicians';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publish' => 'string',
            'price' => 'integer',
        ];
    }

    public function speciality(): BelongsTo
    {
        return $this->belongsTo(Speciality::class, 'specialized_clinics_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function hospitals(): HasMany
    {
        return $this->hasMany(ClinicianHospital::class, 'clinicians_id');
    }

    public function assignmentForHospital(?int $hospitalId = null): ?ClinicianHospital
    {
        $assignments = $this->relationLoaded('hospitals')
            ? $this->hospitals
            : $this->hospitals()->with(['outpatientClinic:id,name_ar,name_en', 'hospital:id,name_ar,name_en'])->get();

        if ($hospitalId !== null && $hospitalId > 0) {
            $matches = $assignments->where('hospital_id', $hospitalId);

            if ($matches->isNotEmpty()) {
                return $matches->first(fn (ClinicianHospital $assignment) => $assignment->clinicNumberLabel() !== '')
                    ?? $matches->first();
            }
        }

        return $assignments->first();
    }

    public function holidayOffers(): HasMany
    {
        return $this->hasMany(HolidayOffer::class, 'clinicians_id');
    }

    public function localizedName(): string
    {
        return $this->resolvePersonName();
    }

    public function localizedDisplayName(): string
    {
        $name = $this->localizedName();

        return $name !== '' ? $name : '—';
    }

    public function hasPersonName(): bool
    {
        return $this->localizedName() !== '';
    }

    public function localizedSecondaryName(): string
    {
        if (! $this->hasPersonName()) {
            return '';
        }

        $nameAr = trim((string) ($this->name_ar ?? ''));
        $nameEn = trim((string) ($this->name_en ?? ''));
        $primary = $this->localizedName();

        if (app()->getLocale() === 'ar') {
            $secondary = $nameEn;
        } else {
            $secondary = $nameAr;
        }

        $secondary = $this->formatPersonName($secondary);

        if ($secondary === '' || $this->normalizeComparable($secondary) === $this->normalizeComparable($primary)) {
            return '';
        }

        if ($this->nameMatchesSpecialization($secondary)) {
            return '';
        }

        return $secondary;
    }

    public function isPlaceholderNameRecord(): bool
    {
        $nameAr = trim((string) ($this->name_ar ?? ''));
        $nameEn = trim((string) ($this->name_en ?? ''));
        $specAr = trim((string) ($this->specialization_ar ?? ''));
        $specEn = trim((string) ($this->specialization_en ?? ''));

        if ($nameAr === '' && $nameEn === '') {
            return false;
        }

        if ($nameAr !== '' && $specAr !== '' && $this->normalizeComparable($nameAr) === $this->normalizeComparable($specAr)) {
            return true;
        }

        return $nameEn !== '' && $specEn !== '' && $this->normalizeComparable($nameEn) === $this->normalizeComparable($specEn);
    }

    public function localizedSpecialization(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->specialization_ar ?: $this->specialization_en));
        }

        return trim((string) ($this->specialization_en ?: $this->specialization_ar));
    }

    private function resolvePersonName(): string
    {
        $nameAr = trim((string) ($this->name_ar ?? ''));
        $nameEn = trim((string) ($this->name_en ?? ''));

        $ordered = app()->getLocale() === 'ar'
            ? [$nameAr, $nameEn]
            : [$nameEn, $nameAr];

        foreach ($ordered as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if ($this->isLikelyPersonName($candidate)) {
                return $this->formatPersonName($candidate);
            }
        }

        foreach ([$nameAr, $nameEn] as $candidate) {
            if ($candidate !== '' && ! $this->nameMatchesSpecialization($candidate)) {
                return $this->formatPersonName($candidate);
            }
        }

        return '';
    }

    private function isLikelyPersonName(string $name): bool
    {
        if ($this->nameMatchesSpecialization($name)) {
            return false;
        }

        if ($this->looksLikeArabicPersonName($name)) {
            return true;
        }

        return $this->looksLikeLatinPersonName($name);
    }

    private function nameMatchesSpecialization(string $name): bool
    {
        $normalized = $this->normalizeComparable($name);

        if ($normalized === '') {
            return true;
        }

        foreach ($this->specializationVariants() as $variant) {
            if ($normalized === $variant) {
                return true;
            }
        }

        if (preg_match('/^(consultant|senior specialist|specialist|physician|dr\.?)\s+/i', $name)) {
            return true;
        }

        if (preg_match('/^(استشاري|أخصائي|اخصائي|كبير\s+أخصائي|طبيب)\s+/u', $name)) {
            return true;
        }

        if (preg_match('/\b(gastroenterology|orthopedic|cardiology|endoscopy|hepatology)\b/i', $name)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function specializationVariants(): array
    {
        $values = [
            (string) ($this->specialization_ar ?? ''),
            (string) ($this->specialization_en ?? ''),
        ];

        if ($this->relationLoaded('speciality') && $this->speciality !== null) {
            $values[] = (string) ($this->speciality->subject_ar ?? '');
            $values[] = (string) ($this->speciality->subject_en ?? '');
        }

        $variants = [];

        foreach ($values as $value) {
            $normalized = $this->normalizeComparable($value);

            if ($normalized !== '') {
                $variants[] = $normalized;
            }
        }

        return array_values(array_unique($variants));
    }

    private function looksLikeArabicPersonName(string $name): bool
    {
        if (! preg_match('/[\x{0600}-\x{06FF}]/u', $name)) {
            return false;
        }

        if (preg_match('/^(استشاري|أخصائي|اخصائي|كبير)/u', trim($name))) {
            return false;
        }

        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($words) && count($words) >= 2;
    }

    private function looksLikeLatinPersonName(string $name): bool
    {
        if (! preg_match('/[A-Za-z]/', $name)) {
            return false;
        }

        if ($this->nameMatchesSpecialization($name)) {
            return false;
        }

        if (preg_match('/^[A-Z][A-Z\s\'\-\.]+$/', $name)) {
            return substr_count($name, ' ') >= 1;
        }

        return (bool) preg_match('/^[A-Z][a-z]+(?:\s+[A-Za-z\'\-\.]+)+$/', $name);
    }

    private function formatPersonName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return '';
        }

        if (preg_match('/^[A-Z][A-Z\s\'\-\.]+$/', $name) && preg_match('/\s/', $name)) {
            return mb_convert_case(mb_strtolower($name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return $name;
    }

    private function normalizeComparable(string $value): string
    {
        $value = trim(strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower($value, 'UTF-8');
    }

    public function localizedQualification(): string
    {
        return trim(strip_tags(html_entity_decode($this->localizedQualificationHtml(), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    public function localizedQualificationHtml(): string
    {
        $raw = app()->getLocale() === 'ar'
            ? ($this->holds_ar ?: $this->holds_en)
            : ($this->holds_en ?: $this->holds_ar);

        return (string) $raw;
    }

    public function hasQualification(): bool
    {
        return $this->localizedQualification() !== '';
    }

    public function localizedCases(): string
    {
        return trim(strip_tags(html_entity_decode($this->localizedCasesHtml(), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    public function localizedCasesHtml(): string
    {
        $raw = app()->getLocale() === 'ar'
            ? ($this->cases_ar ?: $this->cases_en)
            : ($this->cases_en ?: $this->cases_ar);

        return (string) $raw;
    }

    public function hasCases(): bool
    {
        return $this->localizedCases() !== '';
    }

    public function localizedAgeGroup(): string
    {
        return trim((string) $this->age);
    }

    public function photoUrl(): ?string
    {
        if (empty($this->uploaded_file)) {
            return null;
        }

        $base = trim((string) config('hm.doctors_directory.photos_path', 'files'), '/');

        return asset($base.'/'.$this->uploaded_file);
    }
}
