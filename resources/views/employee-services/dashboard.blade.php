@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-employee-services.css') }}?v={{ filemtime(public_path('css/hm-employee-services.css')) }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/hm-services-dashboard-search.js') }}?v={{ filemtime(public_path('js/hm-services-dashboard-search.js')) }}" defer></script>
@endpush

@section('title', __('employee_services.dashboard'))

@section('sidebar_heading', __('employee_services.title'))
@section('sidebar_subheading', __('employee_services.dashboard_subtitle'))

@section('content')
    @php $isRtl = app()->getLocale() === 'ar'; @endphp

    <div class="hm-hs hm-hs--dashboard hm-es">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('employee_services.title'), 'chip' => true],
            ],
        ])

        <section class="hs-dash-hero" aria-labelledby="employeeServicesTitle">
            <div>
                <h1 id="employeeServicesTitle">{{ __('employee_services.dashboard') }}</h1>
                <p>{{ __('employee_services.dashboard_subtitle') }}</p>

                @if (count($cards) > 0)
                    <label class="hs-dash-search" for="servicesDashboardSearch">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input
                            type="search"
                            id="servicesDashboardSearch"
                            placeholder="{{ __('employee_services.dashboard_search_placeholder') }}"
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
                    <a
                        href="{{ $card->url }}"
                        class="hs-dash-card"
                        data-hs-dash-card
                        data-search-text="{{ strtolower($card->title.' '.$card->description) }}"
                        aria-label="{{ $card->title }}"
                    >
                        @if ($card->badge !== '')
                            <span class="es-card-badge">{{ $card->badge }}</span>
                        @endif
                        <div class="hs-dash-card__content">
                            <div class="hs-dash-card__icon" aria-hidden="true">
                                <i class="bi {{ $card->icon }}"></i>
                            </div>
                            <h2 class="hs-dash-card__title">{{ $card->title }}</h2>
                            <span class="hs-dash-card__line" aria-hidden="true"></span>
                            @if ($card->description !== '')
                                <p class="hs-dash-card__desc">{{ $card->description }}</p>
                            @endif
                        </div>
                        <div class="hs-dash-card__bottom">
                            <span class="hs-dash-card__count">&nbsp;</span>
                            <span class="hs-dash-card__arrow" aria-hidden="true">
                                <i class="bi bi-arrow-{{ $isRtl ? 'left' : 'right' }}"></i>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="hs-empty">
                <i class="bi bi-grid" aria-hidden="true"></i>
                <p class="mb-0">{{ __('dashboard.coming_soon') }}</p>
            </div>
        @endif
    </div>
@endsection
