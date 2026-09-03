@extends('layouts.app')

@section('title', $label)

@section('sidebar_heading', $label)
@section('sidebar_subheading', __('service_locations.subtitle'))
@section('figma_page_header', true)

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-service-locations.css') }}?v={{ filemtime(public_path('css/hm-service-locations.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-fm hm-hs sl-opd-figma">
        @include('layouts.partials.figma-module-header', [
            'crumbs' => [
                ['label' => __('dashboard.modules')],
                ['label' => __('service_locations.title'), 'url' => route('modules.service-locations.index')],
                ['label' => $label],
            ],
            'title' => $label,
            'subtitle' => __('service_locations.subtitle'),
            'heroIconSrc' => asset('images/figma/locations/hero.svg'),
            'heroIconSize' => 32,
        ])

        <div class="sl-opd-facts" aria-label="{{ __('service_locations.service_times') }}">
            @foreach ([
                ['number' => 1, 'label' => __('service_locations.extension_number'), 'value' => trim((string) ($duty?->phone_ext ?? '')) ?: '—'],
                ['number' => 2, 'label' => __('service_locations.working_days'), 'value' => $dutyDays],
                ['number' => 3, 'label' => __('service_locations.working_hours'), 'value' => $dutyTime],
                ['number' => 4, 'label' => __('service_locations.floor'), 'value' => $floorName],
            ] as $fact)
                <article class="sl-opd-fact">
                    <span class="sl-opd-fact__number" aria-hidden="true">{{ $fact['number'] }}</span>
                    <span class="sl-opd-fact__copy">
                        <span class="sl-opd-fact__label">{{ $fact['label'] }}</span>
                        <strong>{{ $fact['value'] }}</strong>
                    </span>
                </article>
            @endforeach
        </div>

        @if (count($departmentCards) > 0)
            <div class="hs-filter-head sl-section-head">
                <span class="hs-filter-icon" aria-hidden="true">
                    <img src="{{ asset('images/figma/locations/section.svg') }}" alt="" width="22" height="22">
                </span>
                <h2>{{ __('service_locations.departments') }}</h2>
                <span class="sl-section-count">{{ __('service_locations.departments_count', ['count' => count($departmentCards)]) }}</span>
            </div>

            <div class="hs-dash-grid">
                @foreach ($departmentCards as $card)
                    @include('service-locations.partials.sl-dash-card', [
                        'title' => $card->title,
                        'url' => $card->url,
                        'description' => $card->url ? __('service_locations.department_card_description') : '',
                        'icon' => 'bi-hospital',
                        'iconSrc' => asset('images/figma/locations/card-building.svg'),
                        'countLabel' => $card->url ? __('service_locations.view_departments') : '',
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
