@props([
    'package',
    'cardLayout' => 'standard',
    'isAgreementSection' => false,
])

@php
    use App\Support\HospitalServices\ServiceIcon;

    $modalId = 'svc-'.$package->id;
    $isRooms = $cardLayout === 'rooms';
    $isDiagnostics = $cardLayout === 'diagnostics';
    $isAgreements = $cardLayout === 'agreements' || $isAgreementSection;
    $name = $package->localizedName() ?: '—';
    $code = $package->code1 ?: '—';
    $modalLabel = $isRooms && $package->hasPhotos()
        ? __('hospital_services.view_photos')
        : __('hospital_services.view_details');
    $photoCount = $package->relationLoaded('attachments')
        ? $package->attachments->count()
        : ($package->hasPhotos() ? 1 : 0);
    $duration = $package->localizedResultDuration();
    $priceDisplay = $package->hasPrice() ? $package->formattedPriceWithCurrency() : '—';
@endphp

<article class="hs-svc-card">
    <div id="{{ $modalId }}" hidden class="hm-clinician-modal-source">
        <article class="hm-clinician-popup-card hm-clinician-popup-card--full">
            <div class="hm-clinician-popup-card__accent" aria-hidden="true"></div>

            @include('partials.hm-clinician-popup-header', [
                'title' => $name,
                'subtitle' => $modalLabel,
                'icon' => 'bi-box-seam',
                'closeLabel' => __('hospital_services.close'),
            ])

            <div class="hm-clinician-popup-card__body">
                @include('hospital-services.partials.service-package-card-table', [
                    'package' => $package,
                    'cardLayout' => $cardLayout,
                    'isAgreementSection' => $isAgreementSection,
                    'photoVariant' => $isRooms ? 'gallery' : null,
                ])
            </div>

            <footer class="hm-clinician-popup-card__footer">
                <button type="button" class="btn hm-btn hm-btn--outline hm-clinician-popup-card__close-btn" data-hm-clinician-modal-close>
                    {{ __('hospital_services.close') }}
                </button>
            </footer>
        </article>
    </div>

    <header class="hs-svc-card__head">
        <div class="hs-svc-card__icon" aria-hidden="true">
            @include('hospital-services.partials.hs-icon', [
                'svg' => ServiceIcon::packageSvg($package),
                'size' => 27,
            ])
        </div>
        <div class="hs-svc-card__head-copy">
            <h3 class="hs-svc-card__title">{{ $name }}</h3>
            <span class="hs-svc-card__code">{{ $code }}</span>
        </div>
        <button
            type="button"
            class="hs-svc-card__menu"
            data-hm-doctor-modal
            data-modal-target="{{ $modalId }}"
            aria-label="{{ $modalLabel }}"
        >
            <i class="bi bi-three-dots" aria-hidden="true"></i>
        </button>
    </header>

    <div class="hs-svc-card__body">
        @if ($isAgreements)
            <div class="hs-svc-card__discounts">
                <div class="hs-svc-card__discount">
                    <span class="hs-svc-card__discount-label">{{ __('hospital_services.columns.consultation') }}</span>
                    <span class="hs-svc-card__discount-value">{{ $package->discountValue($package->consultation_discount) }}</span>
                </div>
                <div class="hs-svc-card__discount">
                    <span class="hs-svc-card__discount-label">{{ __('hospital_services.columns.lab_radiology') }}</span>
                    <span class="hs-svc-card__discount-value">{{ $package->discountValue($package->lab_x_rays_discount) }}</span>
                </div>
                <div class="hs-svc-card__discount">
                    <span class="hs-svc-card__discount-label">{{ __('hospital_services.columns.operations') }}</span>
                    <span class="hs-svc-card__discount-value">{{ $package->discountValue($package->operations_hypnosis_discount) }}</span>
                </div>
                <div class="hs-svc-card__discount">
                    <span class="hs-svc-card__discount-label">{{ __('hospital_services.columns.delivery') }}</span>
                    <span class="hs-svc-card__discount-value">{{ $package->discountValue($package->delivery_discount) }}</span>
                </div>
            </div>
        @elseif ($isRooms)
            <div class="hs-svc-card__stats">
                <div class="hs-svc-card__stat">
                    <div class="hs-svc-card__stat-label">{{ __('hospital_services.fields.service_price') }}</div>
                    <div class="hs-svc-card__stat-value">{{ $priceDisplay }}</div>
                </div>
                <div class="hs-svc-card__stat">
                    <div class="hs-svc-card__stat-label">{{ __('hospital_services.fields.photo') }}</div>
                    <div class="hs-svc-card__stat-value">{{ $photoCount > 0 ? __('hospital_services.photos_count', ['count' => $photoCount]) : '—' }}</div>
                </div>
            </div>

            @if ($package->hasPhotos())
                @include('hospital-services.partials.service-package-photos', [
                    'package' => $package,
                    'variant' => 'preview',
                ])
            @endif
        @else
            <div class="hs-svc-card__stats">
                <div class="hs-svc-card__stat">
                    <div class="hs-svc-card__stat-label">{{ __('hospital_services.fields.service_price') }}</div>
                    <div class="hs-svc-card__stat-value">{{ $priceDisplay }}</div>
                </div>
                <div class="hs-svc-card__stat">
                    <div class="hs-svc-card__stat-label">{{ $isDiagnostics ? __('hospital_services.fields.result_duration') : __('hospital_services.fields.duration') }}</div>
                    <div class="hs-svc-card__stat-value hs-svc-card__duration">
                        @if ($duration !== '')
                            <i class="bi bi-clock" aria-hidden="true"></i>
                            {{ $duration }}
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>

            @if ($package->localizedDetails() !== '')
                <div class="hs-svc-card__info">
                    <span class="hs-svc-card__info-label">{{ __('hospital_services.fields.service_details') }}</span>
                    <p class="hs-svc-card__info-text">{!! nl2br(e($package->localizedDetails())) !!}</p>
                </div>
            @endif

            @if ($package->localizedNote() !== '')
                <div class="hs-svc-card__info hs-svc-card__info--note">
                    <span class="hs-svc-card__info-label">{{ $isDiagnostics ? __('hospital_services.fields.preparation') : __('hospital_services.fields.note') }}</span>
                    <p class="hs-svc-card__info-text">{!! nl2br(e($package->localizedNote())) !!}</p>
                </div>
            @endif
        @endif

        <div class="hs-svc-card__view-row">
            <button
                type="button"
                class="hs-svc-card__view-btn"
                data-hm-doctor-modal
                data-modal-target="{{ $modalId }}"
                aria-label="{{ $modalLabel }}"
            >
                <i class="bi bi-eye" aria-hidden="true"></i>
                {{ __('hospital_services.view_details') }}
            </button>
            <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-chevron-left' : 'bi-chevron-right' }} hs-svc-card__view-arrow" aria-hidden="true"></i>
        </div>
    </div>
</article>
