@props([
    'package',
    'cardLayout' => 'standard',
    'isAgreementSection' => false,
    'photoVariant' => null,
])

@php
    $isDiagnostics = $cardLayout === 'diagnostics';
    $isRooms = $cardLayout === 'rooms';
    $isAgreements = $cardLayout === 'agreements' || $isAgreementSection;
    $name = $package->localizedName() ?: '—';
    $code = $package->code1 ?: '—';
@endphp

<div class="hm-service-card__surface{{ $isAgreements ? ' hm-service-card__surface--agreements' : '' }}{{ $isRooms ? ' hm-service-card__surface--rooms' : '' }}">
    <header class="hm-service-card__header">
        <div class="hm-service-card__header-main">
            <h3 class="hm-service-card__title">{{ $name }}</h3>
            <span class="hm-service-card__code">
                <i class="bi bi-upc-scan" aria-hidden="true"></i>
                {{ $code }}
            </span>
        </div>
    </header>

    <div class="hm-service-card__body">
        @if ($isAgreements)
            <div class="hm-service-card__discounts">
                <div class="hm-service-card__discount">
                    <span class="hm-service-card__discount-label">{{ __('hospital_services.columns.consultation') }}</span>
                    <span class="hm-service-card__discount-value">{{ $package->discountValue($package->consultation_discount) }}</span>
                </div>
                <div class="hm-service-card__discount">
                    <span class="hm-service-card__discount-label">{{ __('hospital_services.columns.lab_radiology') }}</span>
                    <span class="hm-service-card__discount-value">{{ $package->discountValue($package->lab_x_rays_discount) }}</span>
                </div>
                <div class="hm-service-card__discount">
                    <span class="hm-service-card__discount-label">{{ __('hospital_services.columns.operations') }}</span>
                    <span class="hm-service-card__discount-value">{{ $package->discountValue($package->operations_hypnosis_discount) }}</span>
                </div>
                <div class="hm-service-card__discount">
                    <span class="hm-service-card__discount-label">{{ __('hospital_services.columns.delivery') }}</span>
                    <span class="hm-service-card__discount-value">{{ $package->discountValue($package->delivery_discount) }}</span>
                </div>
            </div>
        @elseif ($isDiagnostics)
            @if ($package->hasPrice())
                <div class="hm-service-card__price-block">
                    <span class="hm-service-card__price-label">{{ __('hospital_services.fields.service_price') }}</span>
                    <span class="hm-service-card__price-value">{{ $package->formattedPriceWithCurrency() }}</span>
                </div>
            @endif

            @if ($package->localizedResultDuration() !== '')
                <div class="hm-service-card__row">
                    <span class="hm-service-card__row-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
                    <div class="hm-service-card__row-content">
                        <span class="hm-service-card__row-label">{{ __('hospital_services.fields.result_duration') }}</span>
                        <span class="hm-service-card__row-value">{{ $package->localizedResultDuration() }}</span>
                    </div>
                </div>
            @endif

            @if ($package->localizedDetails() !== '')
                <div class="hm-service-card__row">
                    <span class="hm-service-card__row-icon"><i class="bi bi-list-check" aria-hidden="true"></i></span>
                    <div class="hm-service-card__row-content">
                        <span class="hm-service-card__row-label">{{ __('hospital_services.fields.service_details') }}</span>
                        <div class="hm-service-card__row-value hm-service-card__row-value--rich">{!! nl2br(e($package->localizedDetails())) !!}</div>
                    </div>
                </div>
            @endif

            @if ($package->localizedNote() !== '')
                <div class="hm-service-card__note">
                    <div class="hm-service-card__note-label">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        {{ __('hospital_services.fields.preparation') }}
                    </div>
                    <div class="hm-service-card__note-text">{!! nl2br(e($package->localizedNote())) !!}</div>
                </div>
            @endif
        @elseif ($isRooms)
            @if ($package->hasPrice())
                <div class="hm-service-card__price-block">
                    <span class="hm-service-card__price-label">{{ __('hospital_services.fields.service_price') }}</span>
                    <span class="hm-service-card__price-value">{{ $package->formattedPriceWithCurrency() }}</span>
                </div>
            @endif

            @if ($photoVariant && $package->hasPhotos())
                @include('hospital-services.partials.service-package-photos', [
                    'package' => $package,
                    'variant' => $photoVariant,
                ])
            @endif
        @else
            @if ($package->hasPrice())
                <div class="hm-service-card__price-block">
                    <span class="hm-service-card__price-label">{{ __('hospital_services.fields.service_price') }}</span>
                    <span class="hm-service-card__price-value">{{ $package->formattedPriceWithCurrency() }}</span>
                </div>
            @endif

            @if ($package->localizedResultDuration() !== '')
                <div class="hm-service-card__row">
                    <span class="hm-service-card__row-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
                    <div class="hm-service-card__row-content">
                        <span class="hm-service-card__row-label">{{ __('hospital_services.fields.result_duration') }}</span>
                        <span class="hm-service-card__row-value">{{ $package->localizedResultDuration() }}</span>
                    </div>
                </div>
            @endif

            @if ($package->localizedDetails() !== '')
                <div class="hm-service-card__row">
                    <span class="hm-service-card__row-icon"><i class="bi bi-list-check" aria-hidden="true"></i></span>
                    <div class="hm-service-card__row-content">
                        <span class="hm-service-card__row-label">{{ __('hospital_services.fields.service_details') }}</span>
                        <div class="hm-service-card__row-value hm-service-card__row-value--rich">{!! nl2br(e($package->localizedDetails())) !!}</div>
                    </div>
                </div>
            @endif

            @if ($package->localizedNote() !== '')
                <div class="hm-service-card__note">
                    <div class="hm-service-card__note-label">
                        <i class="bi bi-journal-text" aria-hidden="true"></i>
                        {{ __('hospital_services.fields.note') }}
                    </div>
                    <div class="hm-service-card__note-text">{!! nl2br(e($package->localizedNote())) !!}</div>
                </div>
            @endif
        @endif
    </div>
</div>
