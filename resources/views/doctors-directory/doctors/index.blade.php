@extends('layouts.app')

@php
    $isArabic = app()->getLocale() === 'ar';
    $pageTitle = !empty($branchContext)
        ? __('doctors_directory.doctors_at_branch', ['branch' => $branchLabel ?? ''])
        : __('doctors_directory.doctors');
@endphp

@section('title', $pageTitle)

@if (!empty($serviceLocationContext))
    @section('sidebar_heading', $opdLabel)
    @section('sidebar_subheading', $departmentName)
@elseif (!empty($branchContext))
    @section('sidebar_heading', $speciality->localizedName())
    @section('sidebar_subheading', $branchLabel ?? '')
@else
    @section('sidebar_heading', __('doctors_directory.doctors'))
    @section('sidebar_subheading', __('doctors_directory.doctors_subtitle') . ($department->outpatientClinic ? ' · ' . $department->outpatientClinic->localizedName() : ''))
@endif

@push('styles')
    <link href="{{ asset('css/hm-doctors-redesign.css') }}?v={{ filemtime(public_path('css/hm-doctors-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-doctors-directory.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory.css')) }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/hm-doctor-card-modal.js') }}?v={{ filemtime(public_path('js/hm-doctor-card-modal.js')) }}"></script>
    @if (!empty($branchContext) || !empty($hospitalFilterContext))
        <script src="{{ asset('js/hm-searchable-select.js') }}?v={{ filemtime(public_path('js/hm-searchable-select.js')) }}" defer></script>
    @endif
@endpush

@section('content')
    <div class="hm-dd hm-dd--doctors">
        <nav aria-label="{{ __('breadcrumbs.aria_label') }}" class="dd-breadcrumb dd-breadcrumb--bar">@if (!empty($serviceLocationContext))
                <a href="{{ route('modules.service-locations.index') }}">{{ __('service_locations.title') }}</a>
                <span class="dd-breadcrumb-sep" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </span>
                <a href="{{ route('modules.service-locations.show', $opdId) }}">{{ $opdLabel }}</a>
                <span class="dd-breadcrumb-sep" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </span>
                <a href="{{ $indexRoute }}">{{ $departmentName }}</a>
                <span class="dd-breadcrumb-sep" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </span>
                <span class="dd-chip">{{ __('doctors_directory.doctors') }}</span>
            @else
                <a href="{{ route('modules.doctors.specialities.index') }}">{{ __('doctors_directory.title') }}</a>
                <span class="dd-breadcrumb-sep" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </span>
                @if (!empty($branchContext) || !empty($hospitalFilterContext))
                    <a href="{{ route('modules.doctors.specialities.departments', array_filter([
                        'speciality' => $speciality->id,
                        'hospital' => !empty($hospitalFilterContext) ? ($selectedBranchId ?? null) : null,
                    ])) }}">{{ $speciality->localizedName() }}</a>
                @else
                    <span>{{ $speciality->localizedName() }}</span>
                @endif

                @if (!empty($branchContext))
                    <span class="dd-breadcrumb-sep" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                    <span class="dd-chip">{{ $branchLabel }}</span>
                @else
                    @if (!empty($hospitalFilterContext) && !empty($branchLabel))
                        <span class="dd-breadcrumb-sep" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </span>
                        <a href="{{ $departmentsRoute ?? route('modules.doctors.specialities.departments', ['speciality' => $speciality->id, 'hospital' => $selectedBranchId ?? null]) }}">{{ $branchLabel }}</a>
                    @endif
                    @if (!empty($department->outpatientClinic))
                        <span class="dd-breadcrumb-sep" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </span>
                        <span>{{ $department->outpatientClinic->localizedName() }}</span>
                    @else
                        <span class="dd-breadcrumb-sep" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </span>
                        <span>{{ __('doctors_directory.department') }} #{{ $department->id }}</span>
                    @endif
                    <span class="dd-breadcrumb-sep" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                    <span class="dd-chip">{{ __('doctors_directory.doctors') }}</span>
                @endif
            @endif
        </nav>

        <section class="dd-search-card">
            <div class="dd-section-head">
                <div class="dd-section-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M3 4h18l-7 8v6l-4 2v-8L3 4Z"/></svg>
                </div>
                <h2>{{ __('doctors_directory.filters_title') }}</h2>
            </div>

            <form
                method="GET"
                action="{{ $indexRoute ?? route('modules.doctors.departments.doctors.index', [$speciality->id, $department->id]) }}"
            >
                <div class="dd-search-grid">
                    @if (!empty($branchContext) && !empty($clinicOptions) && !empty($branchOptions))
                        @include('partials.hm-searchable-select', [
                            'id' => 'filterClinic',
                            'label' => __('doctors_directory.clinic'),
                            'options' => $clinicOptions,
                            'selected' => $selectedClinicId ?? (string) $speciality->id,
                            'navigateOnSelect' => true,
                            'searchPlaceholder' => __('doctors_directory.search_placeholder'),
                        ])

                        @include('partials.hm-searchable-select', [
                            'id' => 'filterBranch',
                            'label' => __('doctors_directory.branch'),
                            'options' => $branchOptions,
                            'selected' => $selectedBranchId ?? '',
                            'navigateOnSelect' => true,
                            'searchPlaceholder' => __('doctors_directory.search_placeholder'),
                        ])

                        <div class="dd-form-field">
                            <label class="dd-red">{{ __('doctors_directory.all_branches') }}</label>
                            <label class="dd-checkbox-row" title="{{ __('doctors_directory.all_branches_hint') }}">
                                <input class="dd-check" type="checkbox" name="all" value="1" @checked(!empty($filters['all']))>
                                <span>{{ __('doctors_directory.all_branches') }}</span>
                            </label>
                        </div>
                    @elseif (!empty($hospitalFilterContext) && !empty($branchOptions))
                        <div class="dd-form-field">
                            <label class="dd-red" for="filterClinic">{{ __('doctors_directory.clinic') }}</label>
                            <input type="text" id="filterClinic" class="dd-input" value="{{ $speciality->localizedName() }}" readonly tabindex="-1">
                        </div>

                        @if (!empty($department->outpatientClinic))
                            <div class="dd-form-field">
                                <label class="dd-red" for="filterDepartment">{{ __('doctors_directory.department') }}</label>
                                <input type="text" id="filterDepartment" class="dd-input" value="{{ $department->outpatientClinic->localizedName() }}" readonly tabindex="-1">
                            </div>
                        @endif

                        @include('partials.hm-searchable-select', [
                            'id' => 'filterBranch',
                            'label' => __('doctors_directory.branch'),
                            'options' => $branchOptions,
                            'selected' => $selectedBranchId ?? '',
                            'navigateOnSelect' => true,
                            'searchPlaceholder' => __('doctors_directory.search_placeholder'),
                        ])

                        <div class="dd-form-field">
                            <label class="dd-red">{{ __('doctors_directory.all_branches') }}</label>
                            <label class="dd-checkbox-row" title="{{ __('doctors_directory.all_branches_hint') }}">
                                <input class="dd-check" type="checkbox" name="all" value="1" @checked(!empty($filters['all']))>
                                <span>{{ __('doctors_directory.all_branches') }}</span>
                            </label>
                        </div>
                    @else
                        <div class="dd-form-field">
                            <label class="dd-red" for="filterClinic">{{ __('doctors_directory.clinic') }}</label>
                            <input type="text" id="filterClinic" class="dd-input" value="{{ $speciality->localizedName() }}" readonly tabindex="-1">
                        </div>

                        @if (!empty($department->outpatientClinic))
                            <div class="dd-form-field">
                                <label class="dd-red" for="filterDepartment">{{ __('doctors_directory.department') }}</label>
                                <input type="text" id="filterDepartment" class="dd-input" value="{{ $department->outpatientClinic->localizedName() }}" readonly tabindex="-1">
                            </div>
                        @endif
                    @endif

                    <div class="dd-form-field">
                        <label class="dd-red" for="doctorName">{{ __('doctors_directory.doctor_name') }}</label>
                        <input
                            type="search"
                            id="doctorName"
                            name="name"
                            value="{{ $filters['name'] }}"
                            class="dd-input"
                            placeholder="{{ __('doctors_directory.search_name_placeholder') }}"
                            maxlength="100"
                        >
                    </div>

                    <div class="dd-form-field">
                        <label class="dd-red" for="doctorCode">{{ __('doctors_directory.search_code') }}</label>
                        <input
                            type="search"
                            id="doctorCode"
                            name="code"
                            value="{{ $filters['code'] }}"
                            class="dd-input"
                            placeholder="{{ __('doctors_directory.search_code_placeholder') }}"
                            maxlength="40"
                        >
                    </div>
                </div>

                <div class="dd-search-actions">
                    <button type="submit" class="dd-btn dd-btn-primary">
                        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                        {{ __('doctors_directory.search') }}
                    </button>
                    @if ($hasFilters)
                        <a
                            href="{{ $indexRoute ?? route('modules.doctors.departments.doctors.index', [$speciality->id, $department->id]) }}"
                            class="dd-btn dd-btn-outline"
                        >
                            <svg viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
                            {{ __('doctors_directory.reset') }}
                        </a>
                    @endif
                </div>
            </form>
        </section>

        @if ($doctors->count() > 0)
            @foreach ($doctors as $doctor)
                @php
                    $doctorDisplayName = $doctor->localizedDisplayName();
                    $doctorHasPersonName = $doctor->hasPersonName();
                    $doctorSpeciality = $doctor->localizedSpecialization()
                        ?: ($doctor->speciality?->localizedName() ?? '');
                    $cardHospitalId = (!empty($branchContext) || !empty($hospitalFilterContext)) && empty($filters['all'])
                        ? (int) ($selectedBranchId ?? 0)
                        : null;
                    $assignment = $doctor->assignmentForHospital($cardHospitalId > 0 ? $cardHospitalId : null);
                    $hospitalName = (!empty($branchContext) || !empty($hospitalFilterContext)) && empty($filters['all'])
                        ? ($branchLabel ?? '—')
                        : ($assignment?->hospital?->localizedName() ?: ($branchLabel ?? '—'));
                    $clinicBuilding = $assignment?->clinicBuildingLabel() ?: '—';
                    $clinicNumber = $assignment?->clinicNumberLabel() ?: '—';
                    $consultationFee = $doctor->price ?: $assignment?->price;
                    $doctorShowUrl = !empty($serviceLocationContext)
                        ? route('modules.doctors.doctors.show', ['doctor' => $doctor->id, 'opd' => $opdId, 'speciality' => $speciality->id])
                        : route('modules.doctors.doctors.show', $doctor->id);
                @endphp

                <article class="dd-doctor-card">
                    <button
                        type="button"
                        class="dd-expand"
                        data-hm-doctor-modal
                        data-modal-target="card-{{ $doctor->id }}"
                        aria-label="{{ __('doctors_directory.view_profile') }}"
                    >
                        <svg viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
                    </button>

                    <div id="card-{{ $doctor->id }}" hidden class="hm-clinician-modal-source">
                        <article class="hm-clinician-popup-card hm-clinician-popup-card--full">
                            <div class="hm-clinician-popup-card__accent" aria-hidden="true"></div>

                            @include('partials.hm-clinician-popup-header', [
                                'title' => $doctorDisplayName,
                                'subtitle' => __('doctors_directory.view_profile'),
                                'icon' => 'bi-person-badge',
                                'closeLabel' => __('doctors_directory.close'),
                            ])

                            <div class="hm-clinician-popup-card__body">
                                @include('doctors-directory.partials.clinician-card-table', [
                                    'doctor' => $doctor,
                                    'doctorDisplayName' => $doctorDisplayName,
                                    'doctorSpeciality' => $doctorSpeciality,
                                    'hospitalName' => $hospitalName,
                                    'clinicBuilding' => $clinicBuilding,
                                    'clinicNumber' => $clinicNumber,
                                    'consultationFee' => $consultationFee,
                                    'showMoreButtons' => false,
                                    'linkName' => false,
                                ])
                            </div>

                            <footer class="hm-clinician-popup-card__footer">
                                <button type="button" class="btn hm-btn hm-btn--outline hm-clinician-popup-card__close-btn" data-hm-clinician-modal-close>
                                    {{ __('doctors_directory.close') }}
                                </button>
                            </footer>
                        </article>
                    </div>

                    <div id="qual-{{ $doctor->id }}" hidden class="hm-clinician-modal-source">
                        @include('doctors-directory.partials.clinician-popup-row', [
                            'label' => __('doctors_directory.qualification'),
                            'doctorId' => $doctor->id,
                            'type' => 'qual',
                            'doctor' => $doctor,
                        ])
                    </div>

                    <div id="cases-{{ $doctor->id }}" hidden class="hm-clinician-modal-source">
                        @include('doctors-directory.partials.clinician-popup-row', [
                            'label' => __('doctors_directory.cases_seen'),
                            'doctorId' => $doctor->id,
                            'type' => 'cases',
                            'doctor' => $doctor,
                        ])
                    </div>

                    <div class="dd-doctor-hero">
                        <div class="dd-doctor-photo" aria-hidden="true">
                            @if ($doctor->photoUrl())
                                <img
                                    src="{{ $doctor->photoUrl() }}"
                                    alt="{{ $doctorDisplayName }}"
                                    loading="lazy"
                                    decoding="async"
                                    onerror="this.remove();"
                                >
                            @else
                                <svg viewBox="0 0 64 64"><circle cx="32" cy="22" r="13"></circle><path d="M12 58c2.2-13 10.4-21 20-21s17.8 8 20 21H12z"></path></svg>
                            @endif
                        </div>
                        <div class="dd-doctor-info">
                            <h3>
                                @if ($doctorShowUrl && ($doctorHasPersonName ?? true))
                                    <a href="{{ $doctorShowUrl }}" style="color:inherit;text-decoration:none;">{{ $doctorDisplayName }}</a>
                                @else
                                    {{ $doctorDisplayName }}
                                @endif
                            </h3>
                            @if ($doctorSpeciality)
                                <p class="dd-doctor-title">{{ $doctorSpeciality }}</p>
                            @endif
                            <div class="dd-doctor-meta">
                                @if ($doctor->code)
                                    <span><i class="bi bi-person-badge"></i> {{ $doctor->code }}</span>
                                @endif
                                @if ($doctor->country?->localizedName())
                                    <span><i class="bi bi-globe2"></i> {{ $doctor->country->localizedName() }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @include('doctors-directory.partials.clinician-card-details-grid', [
                        'doctor' => $doctor,
                        'hospitalName' => $hospitalName,
                        'clinicBuilding' => $clinicBuilding,
                        'clinicNumber' => $clinicNumber,
                        'consultationFee' => $consultationFee,
                        'showMoreButtons' => true,
                    ])
                </article>
            @endforeach

            <div class="dd-pagination">
                {{ $doctors->links('pagination.hm') }}
            </div>
        @else
            <div class="dd-empty">
                {{ $hasFilters ? __('doctors_directory.no_doctors_search') : __('doctors_directory.no_doctors') }}
            </div>
        @endif

        <div class="dd-back-row dd-page-actions">
            @php
                $backHref = $backToBranchesRoute
                    ?? $backToDepartmentsRoute
                    ?? route('modules.doctors.specialities.departments', $speciality->id);
                $backLabel = !empty($branchContext)
                    ? __('doctors_directory.back_to_branches')
                    : (!empty($serviceLocationContext)
                        ? __('service_locations.back_to_opd')
                        : __('doctors_directory.back_to_departments'));
            @endphp
            <a href="{{ $backHref }}" class="dd-back-btn">
                @if ($isArabic)
                    <svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                @else
                    <svg viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                @endif
                {{ $backLabel }}
            </a>

            @if ((!empty($branchContext) || !empty($hospitalFilterContext)) && !empty($departmentsRoute))
                <a href="{{ $departmentsRoute }}" class="dd-btn-primary">
                    {{ __('doctors_directory.view_departments') }}
                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </a>
            @endif
        </div>
    </div>

    <div class="hm-clinician-modal-page" id="hmDoctorDetailModal" hidden aria-hidden="true">
        <button type="button" class="hm-clinician-modal-backdrop" data-hm-clinician-modal-close tabindex="-1" aria-hidden="true"></button>
        <div class="hm-clinician-modal-center">
            <div class="hm-clinician-modal-panel" role="dialog" aria-modal="true" aria-labelledby="hmDoctorDetailModalTitle">
                <h2 class="visually-hidden" id="hmDoctorDetailModalTitle">{{ __('doctors_directory.view_profile') }}</h2>
                <div class="hm-clinician-modal-panel__body" id="hmDoctorDetailModalBody"></div>
            </div>
        </div>
    </div>
@endsection
