@props([
    'package',
    'variant' => 'preview',
])

@if ($package->hasPhotos())
    <div class="hm-service-card__photos hm-service-card__photos--{{ $variant }}">
        <div class="hm-service-card__photos-head">
            <span class="hm-service-card__photos-label">
                <i class="bi bi-image" aria-hidden="true"></i>
                {{ __('hospital_services.fields.photo') }}
            </span>
            @if ($variant === 'preview' && $package->attachments->count() > 1)
                <span class="hm-service-card__photos-count">
                    {{ __('hospital_services.photos_count', ['count' => $package->attachments->count()]) }}
                </span>
            @endif
        </div>

        @if ($variant === 'preview')
            @php
                $primary = $package->attachments->first();
            @endphp
            @if ($primary)
                <button
                    type="button"
                    class="hm-service-card__photo-preview hm-service-card__photo-trigger"
                    data-hm-photo-lightbox
                    data-photo-src="{{ $primary->url() }}"
                    data-photo-alt="{{ $package->localizedName() }}"
                    aria-label="{{ __('hospital_services.view_photos') }}"
                >
                    <img
                        src="{{ $primary->url() }}"
                        alt="{{ $package->localizedName() }}"
                        class="hm-service-card__photo-img"
                        loading="lazy"
                        decoding="async"
                        onerror="this.hidden=true; var fallback=this.parentElement.querySelector('.hm-service-card__photo-fallback'); if (fallback) fallback.hidden=false;"
                    >
                    @if ($package->attachments->count() > 1)
                        <span class="hm-service-card__photo-count-overlay" aria-hidden="true">
                            <i class="bi bi-image"></i>
                            {{ __('hospital_services.photos_count', ['count' => $package->attachments->count()]) }}
                        </span>
                    @endif
                    <span class="hm-service-card__photo-zoom-hint" aria-hidden="true">
                        <i class="bi bi-zoom-in"></i>
                    </span>
                    <span class="hm-service-card__photo-fallback" hidden>
                        <i class="bi bi-image" aria-hidden="true"></i>
                        {{ __('hospital_services.photo_unavailable') }}
                    </span>
                </button>
            @endif
        @else
            <div class="hm-service-card__photos-grid">
                @foreach ($package->attachments as $attachment)
                    <button
                        type="button"
                        class="hm-service-card__photo-item hm-service-card__photo-trigger"
                        data-hm-photo-lightbox
                        data-photo-src="{{ $attachment->url() }}"
                        data-photo-alt="{{ $package->localizedName() }}"
                        aria-label="{{ __('hospital_services.view_photos') }}"
                    >
                        <img
                            src="{{ $attachment->url() }}"
                            alt="{{ $package->localizedName() }}"
                            class="hm-service-card__photo-img"
                            loading="lazy"
                            decoding="async"
                            onerror="this.hidden=true; var fallback=this.parentElement.querySelector('.hm-service-card__photo-fallback'); if (fallback) fallback.hidden=false;"
                        >
                        <span class="hm-service-card__photo-zoom-hint" aria-hidden="true">
                            <i class="bi bi-zoom-in"></i>
                        </span>
                        <span class="hm-service-card__photo-fallback" hidden>
                            <i class="bi bi-image" aria-hidden="true"></i>
                            {{ __('hospital_services.photo_unavailable') }}
                        </span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
@endif
