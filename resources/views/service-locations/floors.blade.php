@extends('layouts.app')

@section('title', __('service_locations.floors_title'))

@section('sidebar_heading', __('service_locations.floors_title'))
@section('sidebar_subheading', __('service_locations.floors_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-service-locations.css') }}?v={{ filemtime(public_path('css/hm-service-locations.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-hs hm-hs--dashboard">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('service_locations.title'), 'url' => route('modules.service-locations.index')],
                ['label' => __('service_locations.floors_title'), 'chip' => true],
            ],
        ])

        <section class="hs-dash-hero" aria-labelledby="floorsTitle">
            <div>
                <h1 id="floorsTitle">{{ __('service_locations.floors_title') }}</h1>
                <p>{{ __('service_locations.floors_subtitle') }}</p>
            </div>
            <div class="hs-dash-hero-art" aria-hidden="true"></div>
        </section>

        @if ($floors->count() > 0)
            <div class="hs-dash-grid">
                @foreach ($floors as $floor)
                    @php
                        $floorLabel = \App\Support\ServiceLocations\LocalizedLegacyText::floor($floor->name_en ?? null, $floor->name_ar ?? null);
                    @endphp
                    @include('service-locations.partials.sl-dash-card', [
                        'title' => $floorLabel,
                        'url' => route('modules.service-locations.floors.show', $floor->id),
                        'description' => __('service_locations.floor_card_description'),
                        'icon' => 'bi-layers',
                    ])
                @endforeach
            </div>
        @else
            <div class="hs-empty">
                <i class="bi bi-layers" aria-hidden="true"></i>
                <p class="mb-0">{{ __('dashboard.coming_soon') }}</p>
            </div>
        @endif
    </div>
@endsection
