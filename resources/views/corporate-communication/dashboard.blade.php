@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/hm-services-dashboard-search.js') }}?v={{ filemtime(public_path('js/hm-services-dashboard-search.js')) }}" defer></script>
@endpush

@section('title', __('corporate_communication.dashboard'))

@section('sidebar_heading', __('corporate_communication.title'))
@section('sidebar_subheading', __('corporate_communication.dashboard_subtitle'))

@section('content')
    @php $isRtl = app()->getLocale() === 'ar'; @endphp

    <div class="hm-hs hm-hs--dashboard hm-cc">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('corporate_communication.title'), 'chip' => true],
            ],
        ])

        <div class="card hm-cc-hero">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1 card-title">{{ __('corporate_communication.dashboard') }}</h4>
                        <p class="mb-0 text-body">{{ __('corporate_communication.dashboard_subtitle') }}</p>
                    </div>
                </div>

                @if (count($cards) > 0)
                    <label class="hs-dash-search mt-3 mb-0" for="servicesDashboardSearch">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input
                            type="search"
                            id="servicesDashboardSearch"
                            placeholder="{{ __('corporate_communication.dashboard_search_placeholder') }}"
                            autocomplete="off"
                            enterkeyhint="search"
                        >
                    </label>
                @endif
            </div>
        </div>

        @if (count($cards) > 0)
            <div class="row g-3 mt-1" id="servicesDashboardGrid">
                @foreach ($cards as $card)
                    <div class="col-md-6 col-xl-4" data-hs-dash-card data-search-text="{{ strtolower($card->title.' '.$card->description) }}">
                        <a href="{{ $card->url }}" class="card hm-cc-service-card h-100 text-decoration-none">
                            <div class="card-body">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="hm-cc-service-card__icon bg-soft-primary rounded" aria-hidden="true">
                                        <i class="bi {{ $card->icon }} text-primary"></i>
                                    </span>
                                    <div class="flex-grow-1 min-w-0">
                                        <h5 class="mb-1 card-title text-body">{{ $card->title }}</h5>
                                        @if ($card->description !== '')
                                            <p class="mb-0 text-body small">{{ $card->description }}</p>
                                        @endif
                                    </div>
                                    <span class="hm-cc-service-card__arrow text-primary" aria-hidden="true">
                                        <i class="bi bi-arrow-{{ $isRtl ? 'left' : 'right' }}"></i>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
