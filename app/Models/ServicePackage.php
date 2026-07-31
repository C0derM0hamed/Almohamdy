<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePackage extends Model
{
    protected $table = 'service_packages';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public function section(): BelongsTo
    {
        return $this->belongsTo(ServiceSection::class, 'service_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ServicePackageAttachment::class, 'service_packages_id')
            ->orderBy('id');
    }

    public function hasPhotos(): bool
    {
        if ($this->relationLoaded('attachments')) {
            return $this->attachments->isNotEmpty();
        }

        return $this->attachments()->exists();
    }

    public function primaryPhotoUrl(): ?string
    {
        $attachment = $this->relationLoaded('attachments')
            ? $this->attachments->first()
            : $this->attachments()->first(['id', 'file_name']);

        return $attachment?->url();
    }

    public function localizedName(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->name_ar ?: $this->name_en));
        }

        return trim((string) ($this->name_en ?: $this->name_ar));
    }

    public function hasPrice(): bool
    {
        $price = trim((string) $this->price);

        return $price !== '' && $price !== '0';
    }

    public function formattedPrice(): string
    {
        if (! $this->hasPrice()) {
            return '—';
        }

        $price = trim((string) $this->price);

        if (is_numeric($price)) {
            return number_format((float) $price, 0, '.', ',');
        }

        return $price;
    }

    public function formattedPriceWithCurrency(): string
    {
        if (! $this->hasPrice()) {
            return '—';
        }

        $price = trim((string) $this->price);

        if (preg_match('/riyal|rial|ريال|sar/i', $price)) {
            return $price;
        }

        return trim($this->formattedPrice().' '.__('hospital_services.currency'));
    }

    public function localizedResultDuration(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->notice1_ar ?: $this->notice1_en));
        }

        return trim((string) ($this->notice1_en ?: $this->notice1_ar));
    }

    public function localizedDetails(): string
    {
        return trim((string) $this->service_details);
    }

    public function localizedNote(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->notice_ar ?: $this->notice_en));
        }

        return trim((string) ($this->notice_en ?: $this->notice_ar));
    }

    public function discountValue(?string $value): string
    {
        $discount = trim((string) $value);

        if ($discount === '' || $discount === '0') {
            return '—';
        }

        return str_ends_with($discount, '%') ? $discount : $discount.'%';
    }
}
