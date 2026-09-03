@extends('layouts.app')

@php
    $departmentName = app()->getLocale() === 'ar'
        ? preg_replace('/^\s*عيادات\s+/u', '', $speciality->localizedName())
        : $speciality->localizedName();
@endphp

@section('title', $speciality->localizedName())

@section('sidebar_heading', $speciality->localizedName())
@section('sidebar_subheading', __('doctors_directory.speciality_overview_subtitle'))
@section('figma_page_header', true)

@push('workflow_styles')
    <link href="{{ asset('css/hm-doctors-redesign.css') }}?v={{ filemtime(public_path('css/hm-doctors-redesign.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-fm hm-dd hm-dd--overview hm-dd--speciality-figma">
        @include('layouts.partials.figma-module-header', [
            'crumbs' => [
                ['label' => __('dashboard.modules')],
                ['label' => __('doctors_directory.title'), 'url' => route('modules.doctors.specialities.index')],
                ['label' => $speciality->localizedName()],
            ],
            'title' => $speciality->localizedName(),
            'subtitle' => '',
            'heroIconSrc' => asset('images/figma/doctors/speciality-hero.svg'),
            'heroIconSize' => 32,
        ])

        <section class="dd-panel dd-specialty">
            @if ($overviewContent->intro)
                <div class="dd-description-card" dir="rtl">
                    <h2>{{ __('doctors_directory.department_named', ['name' => $departmentName]) }}</h2>
                    <p>{{ $overviewContent->intro }}</p>
                </div>
            @elseif (! $overviewContent->hasContent())
                <div class="dd-description-card" dir="rtl">
                    <h2>{{ __('doctors_directory.department_named', ['name' => $departmentName]) }}</h2>
                    <p>{{ __('doctors_directory.no_speciality_description') }}</p>
                </div>
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
                            <span class="dd-unit-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">{!! \App\Support\DoctorsDirectory\SpecialityIcon::svgForText($unit->text) !!}</svg>
                            </span>
                            <span>{{ $unit->text }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        @if (count($hospitals) > 0)
            <section class="dd-panel dd-branches-panel" aria-labelledby="branchPanelTitle">
                <div id="branchPanelTitle" class="dd-section-title">{{ __('doctors_directory.branches') }}</div>

                <div class="dd-branch-grid">
                    @foreach ($hospitals as $hospital)
                        <a href="{{ $hospital->url }}" class="dd-branch-card" aria-label="{{ $hospital->title }}">
                            @if ($hospital->imageUrl)
                                <span class="dd-branch-photo" style="background-image:url('{{ $hospital->imageUrl }}');"></span>
                            @else
                                <span class="dd-branch-photo dd-branch-photo--placeholder" aria-hidden="true">
                                    <img src="{{ asset('images/figma/locations/card-building.svg') }}" alt="" width="54" height="54">
                                </span>
                            @endif
                            <span class="dd-branch-info">
                                <span class="dd-branch-name">{{ $hospital->title }}</span>
                                <span class="dd-doctor-count">
                                    <img src="{{ asset('images/figma/doctors/branch-doctors.svg') }}" alt="" width="18" height="18">
                                    <strong>{{ $hospital->doctorCount }}</strong> {{ __('doctors_directory.doctor_count') }}
                                </span>
                                <span class="dd-arrow" aria-hidden="true">
                                    <img src="{{ asset('images/figma/doctors/branch-arrow.svg') }}" alt="" width="18" height="18">
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @else
            <div class="dd-empty">
                {{ __('doctors_directory.no_branches') }}
            </div>
        @endif
    </div>
@endsection
