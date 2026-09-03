@extends('layouts.app')

@section('title', __('service_locations.title'))

@section('sidebar_heading', __('service_locations.title'))
@section('sidebar_subheading', __('service_locations.subtitle'))
@section('figma_page_header', true)

@push('scripts')
    <script src="{{ asset('js/hm-services-dashboard-search.js') }}?v={{ filemtime(public_path('js/hm-services-dashboard-search.js')) }}" defer></script>
@endpush

@section('content')
    <div class="hm-fm hm-hs hm-hs--dashboard">
        @include('layouts.partials.figma-module-header', [
            'title' => __('service_locations.title'),
            'subtitle' => __('service_locations.subtitle'),
            'heroIconSrc' => asset('images/figma/locations/hero.svg'),
            'heroIconSize' => 32,
            'crumbs' => [
                ['label' => __('dashboard.modules')],
                ['label' => __('service_locations.title')],
            ],
        ])

        @if (count($cards) > 0)
            <section class="fm-search" aria-labelledby="serviceLocationsSearchTitle">
                <div class="fm-search__head">
                    <h2 id="serviceLocationsSearchTitle">{{ __('service_locations.search_title') }}</h2>
                </div>
                <div class="fm-search__row">
                    <label class="fm-search__query">
                        <img src="{{ asset('images/figma/system/search.svg') }}" alt="" width="18" height="18">
                        <input
                            type="search"
                            id="servicesDashboardSearch"
                            placeholder="{{ __('service_locations.dashboard_search_placeholder') }}"
                            autocomplete="off"
                            enterkeyhint="search"
                        >
                    </label>
                    <button type="button" class="fm-btn--search" id="serviceLocationsSearchBtn">
                        {{ __('hospital_services.search') }}
                    </button>
                </div>
            </section>

            <section class="fm-section">
                @php
                    $locationCount = collect($cards)
                        ->reject(fn ($card) => $card->icon === 'bi-layers')
                        ->sum(fn ($card) => (int) $card->count);
                @endphp
                @include('layouts.partials.figma.section-head', [
                    'title' => __('service_locations.visible_clinics'),
                    'countLabel' => __('service_locations.section_count', ['count' => $locationCount]),
                    'iconHtml' => '<img src="'.e(asset('images/figma/locations/section.svg')).'" alt="" width="22" height="22">',
                ])

                <div class="fm-grid" id="servicesDashboardGrid">
                    @foreach ($cards as $card)
                        @include('layouts.partials.figma-action-card', [
                            'title' => $card->title,
                            'url' => $card->url,
                            'description' => $card->description,
                            'countLabel' => $card->countLabel,
                            'iconSrc' => asset('images/figma/locations/card-building.svg'),
                            'actionLabel' => __('service_locations.view_departments'),
                            'searchText' => strtolower($card->title.' '.$card->description),
                            'titleTag' => 'h3',
                        ])
                    @endforeach
                </div>
            </section>
        @else
            <div class="fm-empty">
                <p class="mb-0">{{ __('dashboard.coming_soon') }}</p>
            </div>
        @endif
    </div>
@endsection
