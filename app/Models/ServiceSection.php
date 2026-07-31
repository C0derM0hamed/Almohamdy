<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceSection extends Model
{
    protected $table = 'services_sections';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function packages(): HasMany
    {
        return $this->hasMany(ServicePackage::class, 'service_id');
    }

    public function localizedName(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->name_ar ?: $this->name_en));
        }

        return trim((string) ($this->name_en ?: $this->name_ar));
    }

    public function legacyNavName(): string
    {
        $labels = config('hm.hospital_services.section_nav_labels.'.(int) $this->id);

        if (is_array($labels)) {
            $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
            $label = trim((string) ($labels[$locale] ?? $labels['ar'] ?? $labels['en'] ?? ''));

            if ($label !== '') {
                return $label;
            }
        }

        return $this->localizedName();
    }

    public function isPublished(): bool
    {
        return (string) $this->publish === '1';
    }
}
