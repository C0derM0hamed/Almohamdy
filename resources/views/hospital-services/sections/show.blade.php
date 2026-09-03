@extends('layouts.app')

@php
    use App\Support\HospitalServices\SectionNavPresentation;

    $pageTitle = $section->legacyNavName();
    $sectionId = (int) $section->id;
@endphp

@section('title', $pageTitle)

@section('sidebar_heading', __('hospital_services.title'))
@section('sidebar_subheading', $pageTitle)
@section('figma_page_header', true)

@push('styles')
    <link href="{{ asset('css/hm-hospital-services.css') }}?v={{ filemtime(public_path('css/hm-hospital-services.css')) }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/hm-doctor-card-modal.js') }}?v={{ $hmAssetVersion }}"></script>
    <script src="{{ asset('js/hm-service-photo-lightbox.js') }}?v={{ $hmAssetVersion }}"></script>
    <script src="{{ asset('js/hm-searchable-select.js') }}?v={{ filemtime(public_path('js/hm-searchable-select.js')) }}" defer></script>
@endpush

@section('content')
    <div class="hm-fm hm-hs hm-hs--section">
        @include('layouts.partials.figma-module-header', [
            'title' => $pageTitle,
            'subtitle' => SectionNavPresentation::pageSubtitleFor($sectionId),
            'heroIconSrc' => SectionNavPresentation::figmaHeroIcon($sectionId),
            'heroIconSize' => 32,
            'crumbs' => [
                ['label' => __('dashboard.modules')],
                ['label' => $pageTitle],
            ],
        ])

        <section class="fm-search" aria-labelledby="sectionFiltersTitle">
            <div class="fm-search__head">
                <h2 id="sectionFiltersTitle">{{ __('hospital_services.filters_title') }}</h2>
                <p>{{ __('hospital_services.filters_subtitle') }}</p>
            </div>
            <form
                method="GET"
                action="{{ route('modules.services.sections.show', $section->id) }}"
                class="fm-search__row"
            >
                <div class="fm-field">
                    @include('partials.hm-searchable-select', [
                        'id' => 'filterServiceSection',
                        'label' => __('hospital_services.fields.section'),
                        'options' => $sectionOptions,
                        'selected' => (string) $section->id,
                        'navigateOnSelect' => true,
                        'searchPlaceholder' => __('hospital_services.section_search_placeholder'),
                    ])
                </div>
                <div class="fm-field">
                    <label for="serviceCode">{{ __('hospital_services.fields.service_code') }}</label>
                    <input
                        class="fm-input"
                        type="search"
                        id="serviceCode"
                        name="search"
                        value="{{ $search }}"
                        placeholder="{{ __('hospital_services.code_search_placeholder') }}"
                        maxlength="100"
                        autocomplete="off"
                    >
                </div>
                <button type="submit" class="fm-btn--search">{{ __('hospital_services.search') }}</button>
                <a href="{{ route('modules.services.sections.show', $section->id) }}" class="fm-btn--reset">
                    {{ __('hospital_services.reset') }}
                    <img src="{{ asset('images/figma/doctors/reset.svg') }}" alt="" width="18" height="18">
                </a>
            </form>
        </section>

        @if ($packages->count() > 0)
            <section class="fm-section">
                @include('layouts.partials.figma.section-head', [
                    'title' => $isAgreementSection ? $pageTitle : __('hospital_services.section_services', ['section' => $pageTitle]),
                    'countLabel' => $isAgreementSection
                        ? __('hospital_services.partnerships_count', ['count' => $packages->total()])
                        : __('hospital_services.services_count', ['count' => $packages->total()]),
                    'iconHtml' => '<img src="'.e(SectionNavPresentation::figmaCardIcon($sectionId)).'" alt="" width="22" height="22">',
                ])

                <div class="fm-pkg-grid">
                    @foreach ($packages as $package)
                        @include('hospital-services.partials.service-package-card', [
                            'package' => $package,
                            'cardLayout' => $cardLayout,
                            'isAgreementSection' => $isAgreementSection,
                        ])
                    @endforeach
                </div>

                @include('layouts.partials.figma.pagination', [
                    'paginator' => $packages,
                    'summaryKey' => 'hospital_services.results_summary',
                ])
            </section>
        @else
            <div class="fm-empty">
                <p class="mb-0">{{ $hasFilters ? __('hospital_services.no_results') : __('hospital_services.no_services') }}</p>
            </div>
        @endif
    </div>

    <div class="hm-clinician-modal-page" id="hmDoctorDetailModal" hidden aria-hidden="true">
        <button type="button" class="hm-clinician-modal-backdrop" data-hm-clinician-modal-close tabindex="-1" aria-hidden="true"></button>
        <div class="hm-clinician-modal-center">
            <div class="hm-clinician-modal-panel" role="dialog" aria-modal="true" aria-labelledby="hmDoctorDetailModalTitle">
                <h2 id="hmDoctorDetailModalTitle" class="visually-hidden">{{ __('hospital_services.view_details') }}</h2>
                <div class="hm-clinician-modal-panel__body" id="hmDoctorDetailModalBody"></div>
            </div>
        </div>
    </div>

    <div class="hm-service-photo-lightbox" id="hmServicePhotoLightbox" hidden aria-hidden="true">
        <button type="button" class="hm-service-photo-lightbox__backdrop" data-hm-photo-lightbox-close tabindex="-1" aria-hidden="true"></button>
        <div class="hm-service-photo-lightbox__center" role="dialog" aria-modal="true" aria-label="{{ __('hospital_services.view_photos') }}">
            <button
                type="button"
                class="hm-service-photo-lightbox__close"
                data-hm-photo-lightbox-close
                aria-label="{{ __('hospital_services.close') }}"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
            <img src="" alt="" class="hm-service-photo-lightbox__img" id="hmServicePhotoLightboxImg">
        </div>
    </div>
@endsection
