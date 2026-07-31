@extends('layouts.app')

@section('title', __('service_locations.title'))

@section('sidebar_heading', __('service_locations.title'))
@section('sidebar_subheading', __('service_locations.subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-service-locations.css') }}?v={{ filemtime(public_path('css/hm-service-locations.css')) }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/hm-services-dashboard-search.js') }}?v={{ filemtime(public_path('js/hm-services-dashboard-search.js')) }}" defer></script>
@endpush

@section('content')
    <div class="hm-hs hm-hs--dashboard">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('service_locations.title'), 'chip' => true],
            ],
        ])

        <section class="hs-dash-hero" aria-labelledby="serviceLocationsTitle">
            <div>
                <h1 id="serviceLocationsTitle">{{ __('service_locations.title') }}</h1>
                <p>{{ __('service_locations.subtitle') }}</p>

                @if (count($cards) > 0)
                    <label class="hs-dash-search" for="servicesDashboardSearch">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input
                            type="search"
                            id="servicesDashboardSearch"
                            placeholder="{{ __('service_locations.dashboard_search_placeholder') }}"
                            autocomplete="off"
                            enterkeyhint="search"
                        >
                    </label>
                @endif
            </div>
            <div class="hs-dash-hero-art" aria-hidden="true"></div>
        </section>

        @if (count($cards) > 0)
            <div class="hs-dash-grid" id="servicesDashboardGrid">
                @foreach ($cards as $card)
                    @include('service-locations.partials.sl-dash-card', [
                        'title' => $card->title,
                        'url' => $card->url,
                        'description' => $card->description,
                        'countLabel' => $card->countLabel,
                        'icon' => $card->icon,
                        'searchText' => strtolower($card->title.' '.$card->description),
                    ])
                @endforeach
            </div>
        @else
            <div class="hs-empty">
                <i class="bi bi-building" aria-hidden="true"></i>
                <p class="mb-0">{{ __('dashboard.coming_soon') }}</p>
            </div>
        @endif
    </div>
@endsection
