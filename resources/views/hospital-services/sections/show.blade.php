@extends('layouts.app')

@php
    use App\Support\HospitalServices\SectionNavPresentation;

    $pageTitle = $section->legacyNavName();
    $sectionId = (int) $section->id;
@endphp

@section('title', $pageTitle)

@section('sidebar_heading', __('hospital_services.title'))
@section('sidebar_subheading', $pageTitle)

@push('styles')
    <link href="{{ asset('css/hm-components.css') }}?v={{ $hmAssetVersion }}" rel="stylesheet">
    <link href="{{ asset('css/hm-hospital-services.css') }}?v={{ filemtime(public_path('css/hm-hospital-services.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/hm-doctor-card-modal.js') }}?v={{ $hmAssetVersion }}"></script>
    <script src="{{ asset('js/hm-service-photo-lightbox.js') }}?v={{ $hmAssetVersion }}"></script>
    <script src="{{ asset('js/hm-searchable-select.js') }}?v={{ filemtime(public_path('js/hm-searchable-select.js')) }}" defer></script>
@endpush

@section('content')
    <div class="hm-hs hm-hs--section">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('hospital_services.title'), 'url' => route('modules.hospital-services')],
                ['label' => $pageTitle, 'chip' => true],
            ],
        ])

        <section class="hs-page-hero" aria-labelledby="sectionPageTitle">
            <div>
                <h1 id="sectionPageTitle">{{ $pageTitle }}</h1>
                <p>{{ SectionNavPresentation::pageSubtitleFor($sectionId) }}</p>
            </div>
            <div class="hs-page-hero-art" aria-hidden="true"></div>
        </section>

        <div class="hs-filter-card">
            <div class="hs-filter-head">
                <span class="hs-filter-icon" aria-hidden="true"><i class="bi bi-funnel"></i></span>
                <h2>{{ __('hospital_services.filters_title') }}</h2>
            </div>

            <form
                method="GET"
                action="{{ route('modules.services.sections.show', $section->id) }}"
                class="hs-filter-grid"
            >
                <div class="hs-field hs-field--select">
                    @include('partials.hm-searchable-select', [
                        'id' => 'filterServiceSection',
                        'label' => __('hospital_services.fields.section'),
                        'options' => $sectionOptions,
                        'selected' => (string) $section->id,
                        'navigateOnSelect' => true,
                        'searchPlaceholder' => __('hospital_services.section_search_placeholder'),
                    ])
                </div>

                <div class="hs-field hs-field--search">
                    <div class="hs-input-wrap hm-hope-search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input
                            type="search"
                            id="serviceCode"
                            name="search"
                            value="{{ $search }}"
                            aria-label="{{ __('hospital_services.fields.service_code') }}"
                            placeholder="{{ __('hospital_services.code_search_placeholder') }}"
                            maxlength="100"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                        >
                    </div>
                </div>

                <button type="submit" class="hs-btn hs-btn--primary">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    {{ __('hospital_services.search') }}
                </button>

                @if ($hasFilters)
                    <a
                        href="{{ route('modules.services.sections.show', $section->id) }}"
                        class="hs-btn hs-btn--ghost"
                    >
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        {{ __('hospital_services.reset') }}
                    </a>
                @endif
            </form>
        </div>

        @if ($packages->count() > 0)
            <div class="hs-svc-grid">
                @foreach ($packages as $package)
                    @include('hospital-services.partials.service-package-card', [
                        'package' => $package,
                        'cardLayout' => $cardLayout,
                        'isAgreementSection' => $isAgreementSection,
                    ])
                @endforeach
            </div>

            <div class="hm-pagination-wrap d-flex justify-content-center">
                {{ $packages->links('pagination.hm') }}
            </div>
        @else
            <div class="hs-empty">
                <i class="bi bi-search" aria-hidden="true"></i>
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
                class="hm-service-photo-lightbox__close btn-close"
                data-hm-photo-lightbox-close
                aria-label="{{ __('hospital_services.close') }}"
            ></button>
            <img src="" alt="" class="hm-service-photo-lightbox__img" id="hmServicePhotoLightboxImg">
        </div>
    </div>
@endsection
