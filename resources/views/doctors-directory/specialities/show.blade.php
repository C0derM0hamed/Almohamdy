@extends('layouts.app')

@php
    $specialityIconSvg = \App\Support\DoctorsDirectory\SpecialityIcon::svgFor($speciality);
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', $speciality->localizedName())

@section('sidebar_heading', $speciality->localizedName())
@section('sidebar_subheading', __('doctors_directory.speciality_overview_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-doctors-redesign.css') }}?v={{ filemtime(public_path('css/hm-doctors-redesign.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-dd hm-dd--overview">
        @include('doctors-directory.partials.dd-breadcrumb', [
            'variant' => 'bar',
            'items' => [
                ['label' => __('doctors_directory.title'), 'url' => route('modules.doctors.specialities.index')],
                ['label' => $speciality->localizedName(), 'chip' => true],
            ],
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
            @elseif (! $overviewContent->hasContent())
                <div class="dd-description-card" dir="rtl">
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
                                    <svg viewBox="0 0 24 24"><path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16"/><path d="M9 21v-6h6v6M8 7h.01M12 7h.01M16 7h.01M8 11h.01M12 11h.01M16 11h.01"/></svg>
                                </span>
                            @endif
                            <span class="dd-branch-info">
                                <span class="dd-branch-name">{{ $hospital->title }}</span>
                                <span class="dd-doctor-count">
                                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    <strong>{{ $hospital->doctorCount }}</strong> {{ __('doctors_directory.doctor_count') }}
                                </span>
                                <span class="dd-arrow" aria-hidden="true">
                                    @if ($isRtl)
                                        <svg viewBox="0 0 24 24"><path d="M19 12H5M11 5l-7 7 7 7"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                                    @endif
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
