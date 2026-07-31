@extends('layouts.app')

@php
    $specialityIconSvg = \App\Support\DoctorsDirectory\SpecialityIcon::svgFor($speciality);
    $isRtl = app()->getLocale() === 'ar';
    $overviewContent = \App\Support\DoctorsDirectory\SpecialityDescription::overviewContent($speciality);
    $hospitalLabel = $selectedHospital !== null
        ? \App\Support\LocaleText::localizedField(
            (string) ($selectedHospital->name_ar ?? ''),
            (string) ($selectedHospital->name_en ?? ''),
        )
        : null;

    $breadcrumbItems = [
        ['label' => __('doctors_directory.title'), 'url' => route('modules.doctors.specialities.index')],
    ];

    if (count($hospitals) >= 1) {
        $breadcrumbItems[] = [
            'label' => $speciality->localizedName(),
            'url' => route('modules.doctors.specialities.departments', $speciality->id),
        ];
    }

    $breadcrumbItems[] = [
        'label' => $hospitalLabel ?? $speciality->localizedName(),
        'chip' => true,
    ];
@endphp

@section('title', $speciality->localizedName())

@section('sidebar_heading', $speciality->localizedName())
@section('sidebar_subheading', __('doctors_directory.departments_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-doctors-redesign.css') }}?v={{ filemtime(public_path('css/hm-doctors-redesign.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-dd hm-dd--departments">
        @include('doctors-directory.partials.dd-breadcrumb', [
            'variant' => 'bar',
            'items' => $breadcrumbItems,
        ])

        <section class="dd-panel dd-specialty">
            <div class="dd-title-line">
                <span class="dd-title-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">{!! $specialityIconSvg !!}</svg>
                </span>
                <h2>{{ $speciality->localizedName() }}</h2>
            </div>

            @if ($overviewContent->intro)
                <div class="dd-description-card" dir="rtl">
                    <p>{{ $overviewContent->intro }}</p>
                </div>
            @elseif ($description && ! $overviewContent->hasContent())
                <div class="dd-description-card dd-description-card--rich" dir="rtl">{!! $description !!}</div>
            @endif

            @if ($overviewContent->unitsHeading)
                <div class="dd-units-trigger" dir="rtl">
                    <span class="dd-trigger-dot" aria-hidden="true"></span>
                    <span>{{ $overviewContent->unitsHeading }}</span>
                </div>
            @endif

            @if (count($overviewContent->units) > 0)
                <div class="dd-units-grid" dir="rtl">
                    @foreach ($overviewContent->units as $unit)
                        <div class="dd-unit-row">
                            <span class="dd-unit-no">{{ $unit->number }}</span>
                            <span>{{ $unit->text }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        @if ($speciality->outpatientSections->isNotEmpty())
            <section class="dd-panel dd-departments-panel" aria-labelledby="departmentsPanelTitle">
                <div id="departmentsPanelTitle" class="dd-section-title">{{ __('doctors_directory.departments') }}</div>

                @if ($hospitalLabel)
                    <p class="dd-section-subtitle">{{ __('doctors_directory.departments_at_branch', ['branch' => $hospitalLabel]) }}</p>
                @else
                    <p class="dd-section-subtitle">{{ __('doctors_directory.departments_list_hint') }}</p>
                @endif

                <label class="dd-field dd-dept-search" for="departmentLiveSearch">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input
                        type="search"
                        id="departmentLiveSearch"
                        placeholder="{{ __('doctors_directory.search_departments_placeholder') }}"
                        aria-label="{{ __('doctors_directory.search_departments_placeholder') }}"
                        autocomplete="off"
                        enterkeyhint="search"
                    >
                </label>

                <div
                    id="departmentSearchEmpty"
                    class="dd-dept-search-empty"
                    role="status"
                    aria-live="polite"
                    hidden
                >
                    <div class="dd-dept-search-empty__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <h3>{{ __('doctors_directory.no_departments_search_title') }}</h3>
                    <p>{{ __('doctors_directory.no_departments_search') }}</p>
                </div>

                <div class="dd-department-grid" id="departmentCardsGrid">
                    @foreach ($speciality->outpatientSections as $section)
                        @php
                            $departmentName = $section->outpatientClinic
                                ? $section->outpatientClinic->localizedName()
                                : __('doctors_directory.department').' #'.$section->id;
                            $departmentNameAr = $section->outpatientClinic
                                ? trim((string) ($section->outpatientClinic->name_ar ?: $section->outpatientClinic->name_en))
                                : '';
                            $departmentNameEn = $section->outpatientClinic
                                ? (\App\Support\LocaleText::outpatientClinicLabel($section->outpatientClinic->name_ar)
                                    ?? trim((string) ($section->outpatientClinic->name_en ?: $section->outpatientClinic->name_ar)))
                                : __('doctors_directory.department').' #'.$section->id;
                            $departmentDoctorCount = (int) ($section->doctors_count ?? 0);
                            $departmentIsEmpty = $departmentDoctorCount === 0;
                        @endphp
                        <a
                            href="{{ route('modules.doctors.departments.doctors.index', array_filter([
                                'speciality' => $speciality->id,
                                'department' => $section->id,
                                'hospital' => $selectedHospital?->id,
                            ])) }}"
                            class="dd-department-card js-department-card @if ($departmentIsEmpty) is-empty @endif"
                            aria-label="{{ $departmentName }}"
                            data-name-ar="{{ $departmentNameAr }}"
                            data-name-en="{{ $departmentNameEn }}"
                            data-display-title="{{ $departmentName }}"
                        >
                            <span class="dd-department-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/></svg>
                            </span>
                            <span class="dd-department-card__title">{{ $departmentName }}</span>
                            @if ($departmentDoctorCount > 0)
                                <span class="dd-department-card__meta">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    <strong>{{ $departmentDoctorCount }}</strong> {{ __('doctors_directory.doctor_count') }}
                                </span>
                            @endif
                            <span class="dd-department-card__arrow" aria-hidden="true">
                                @if ($isRtl)
                                    <svg viewBox="0 0 24 24"><path d="M19 12H5M11 5l-7 7 7 7"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @else
            <div class="dd-empty">
                <strong>{{ __('doctors_directory.empty_departments_title') }}</strong>
                <p>{{ __('doctors_directory.no_departments') }}</p>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/hm-department-search.js') }}?v={{ filemtime(public_path('js/hm-department-search.js')) }}" defer></script>
@endpush
