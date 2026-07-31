@extends('layouts.app')

@section('title', $label)

@section('sidebar_heading', $label)
@section('sidebar_subheading', __('service_locations.subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-service-locations.css') }}?v={{ filemtime(public_path('css/hm-service-locations.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-hs">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('service_locations.title'), 'url' => route('modules.service-locations.index')],
                ['label' => $label, 'chip' => true],
            ],
        ])

        <section class="hs-page-hero" aria-labelledby="opdLocationTitle">
            <div>
                <h1 id="opdLocationTitle">{{ $label }}</h1>
                <p>{{ __('service_locations.subtitle') }}</p>
            </div>
            <div class="hs-page-hero-art" aria-hidden="true"></div>
        </section>

        <div class="hs-list-panel sl-duty-panel">
            <table class="hm-opd-info-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('service_locations.extension_number') }}</th>
                        <th scope="col">{{ __('service_locations.working_days') }}</th>
                        <th scope="col">{{ __('service_locations.working_hours') }}</th>
                        <th scope="col">{{ __('service_locations.floor') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ trim((string) ($duty?->phone_ext ?? '')) ?: '—' }}</td>
                        <td>{{ $dutyDays }}</td>
                        <td>{{ $dutyTime }}</td>
                        <td>{{ $floorName }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if (count($departmentCards) > 0)
            <div class="hs-filter-head sl-section-head">
                <span class="hs-filter-icon" aria-hidden="true"><i class="bi bi-hospital"></i></span>
                <h2>{{ __('service_locations.departments') }}</h2>
            </div>

            <div class="hs-dash-grid">
                @foreach ($departmentCards as $card)
                    @include('service-locations.partials.sl-dash-card', [
                        'title' => $card->title,
                        'url' => $card->url,
                        'description' => $card->url ? __('service_locations.department_card_description') : '',
                        'icon' => 'bi-hospital',
                        'headingLevel' => 3,
                    ])
                @endforeach
            </div>
        @else
            <div class="hs-empty">
                <i class="bi bi-building" aria-hidden="true"></i>
                <p class="mb-0">{{ __('service_locations.no_departments') }}</p>
                <a href="{{ route('modules.service-locations.index') }}" class="hs-btn hs-btn--ghost mt-3">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('service_locations.back_to_locations') }}
                </a>
            </div>
        @endif
    </div>
@endsection
