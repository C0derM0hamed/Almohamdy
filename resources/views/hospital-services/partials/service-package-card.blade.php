@props([
    'package',
    'cardLayout' => 'standard',
    'isAgreementSection' => false,
])

@php
    use App\Support\HospitalServices\ServiceIcon;
    use Illuminate\Support\Str;

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
    $durationLabel = $isDiagnostics
        ? __('hospital_services.fields.result_duration')
        : __('hospital_services.fields.duration');
    $details = $package->localizedDetails();
    $note = $package->localizedNote();
    $noteLabel = $isDiagnostics
        ? __('hospital_services.fields.preparation')
        : __('hospital_services.fields.note');
    $roomIcon = $isRooms
        ? (Str::contains(Str::lower($name), ['royal', 'ملكي'])
            ? 'crown'
            : (Str::contains(Str::lower($name), ['suite', 'جناح']) ? 'bed' : 'door'))
        : null;
@endphp

<article class="fm-pkg{{ $isRooms ? ' fm-pkg--rooms' : '' }}{{ $isAgreements ? ' fm-pkg--agreements' : '' }}">
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

    <header class="fm-pkg__hero">
        <div class="fm-pkg__tools">
            <button
                type="button"
                class="fm-pkg__more"
                data-hm-doctor-modal
                data-modal-target="{{ $modalId }}"
                aria-label="{{ $modalLabel }}"
            >
                <img src="{{ asset('images/figma/system/pkg-more.svg') }}" alt="" width="18" height="18">
            </button>
            <span class="fm-pkg__mark" aria-hidden="true">
                @if ($isAgreements)
                    <img src="{{ asset('images/figma/services/pkg-heart.svg') }}" alt="" width="20" height="20">
                @elseif ($isRooms)
                    <img src="{{ asset('images/figma/services/pkg-room-'.$roomIcon.'.svg') }}" alt="" width="20" height="20">
                @else
                    @include('hospital-services.partials.hs-icon', [
                        'svg' => ServiceIcon::packageSvg($package),
                        'size' => 20,
                    ])
                @endif
            </span>
        </div>
        <h3 class="fm-pkg__title">{{ $name }}</h3>
        @unless ($isRooms)
            <span class="fm-pkg__code">{{ $code }}</span>
        @endunless
    </header>

    <div class="fm-pkg__body">
        @if ($isAgreements)
            <div class="fm-pkg__discounts">
                <div class="fm-pkg__discount">
                    <span class="fm-pkg__label">{{ __('hospital_services.columns.consultation') }}</span>
                    <span class="fm-pkg__percent">{{ $package->discountValue($package->consultation_discount) }}</span>
                </div>
                <div class="fm-pkg__discount">
                    <span class="fm-pkg__label">{{ __('hospital_services.columns.operations') }}</span>
                    <span class="fm-pkg__percent">{{ $package->discountValue($package->operations_hypnosis_discount) }}</span>
                </div>
                <div class="fm-pkg__discount">
                    <span class="fm-pkg__label">{{ __('hospital_services.columns.delivery') }}</span>
                    <span class="fm-pkg__percent">{{ $package->discountValue($package->delivery_discount) }}</span>
                </div>
                <div class="fm-pkg__discount">
                    <span class="fm-pkg__label">{{ __('hospital_services.columns.lab_radiology') }}</span>
                    <span class="fm-pkg__percent">{{ $package->discountValue($package->lab_x_rays_discount) }}</span>
                </div>
            </div>
        @else
            <div class="fm-pkg__meta">
                <div class="fm-pkg__meta-item">
                    <span class="fm-pkg__label">{{ $isRooms ? __('hospital_services.fields.photo') : $durationLabel }}</span>
                    <span class="fm-pkg__value">
                        @if ($isRooms)
                            {{ $photoCount > 0 ? __('hospital_services.photos_count', ['count' => $photoCount]) : '—' }}
                        @elseif ($duration !== '')
                            <img src="{{ asset('images/figma/system/pkg-clock.svg') }}" alt="" width="15" height="15">
                            {{ $duration }}
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="fm-pkg__meta-item">
                    <span class="fm-pkg__label">{{ __('hospital_services.fields.service_price') }}</span>
                    <span class="fm-pkg__value">
                        @if ($package->hasPrice())
                            <span class="fm-pkg__price">{{ $package->formattedPrice() }}</span>
                            <span class="fm-pkg__currency">{{ __('hospital_services.currency') }}</span>
                        @else
                            —
                        @endif
                    </span>
                </div>
            </div>

            @if ($isRooms && $package->hasPhotos())
                @include('hospital-services.partials.service-package-photos', [
                    'package' => $package,
                    'variant' => 'preview',
                ])
            @endif

            @if ($details !== '')
                <div>
                    <div class="fm-pkg__desc-head">
                        <span class="fm-icon-20">
                            <img src="{{ asset('images/figma/system/pkg-desc.svg') }}" alt="" width="12" height="12">
                        </span>
                        {{ __('hospital_services.fields.service_details') }}
                    </div>
                    <p class="fm-pkg__desc">{!! nl2br(e($details)) !!}</p>
                </div>
            @endif

            @if ($note !== '')
                <div class="fm-pkg__note">
                    <p class="fm-pkg__note-title">
                        <span class="fm-icon-22">
                            <img src="{{ asset('images/figma/system/pkg-note.svg') }}" alt="" width="12" height="12">
                        </span>
                        {{ $noteLabel }}
                    </p>
                    <p>{!! nl2br(e($note)) !!}</p>
                </div>
            @endif
        @endif

        <div class="fm-pkg__foot">
            <button
                type="button"
                class="fm-btn--icon"
                data-hm-doctor-modal
                data-modal-target="{{ $modalId }}"
                aria-label="{{ $modalLabel }}"
            >
                <img src="{{ asset('images/figma/system/pkg-arrow.svg') }}" alt="" width="18" height="18">
            </button>
            <button
                type="button"
                class="fm-btn--ghost"
                data-hm-doctor-modal
                data-modal-target="{{ $modalId }}"
            >
                {{ $modalLabel }}
                <img src="{{ asset('images/figma/system/pkg-eye.svg') }}" alt="" width="17" height="17">
            </button>
        </div>
    </div>
</article>
