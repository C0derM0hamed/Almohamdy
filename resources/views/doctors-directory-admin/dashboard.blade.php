@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-doctors-directory-admin.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory-admin.css')) }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/hm-services-dashboard-search.js') }}?v={{ filemtime(public_path('js/hm-services-dashboard-search.js')) }}" defer></script>
@endpush

@section('title', __('doctors_directory_admin.dashboard'))

@section('sidebar_heading', __('doctors_directory_admin.title'))
@section('sidebar_subheading', __('doctors_directory_admin.dashboard_subtitle'))

@section('content')
    <div class="hm-hs hm-hs--dashboard hm-dda">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('doctors_directory_admin.dashboard'), 'chip' => true],
            ],
        ])

        <section class="hs-dash-hero" aria-labelledby="ddaDashboardTitle">
            <div>
                <h1 id="ddaDashboardTitle">{{ __('doctors_directory_admin.dashboard') }}</h1>
                <p>{{ __('doctors_directory_admin.dashboard_subtitle') }}</p>

                @if (count($cards) > 0)
                    <label class="hs-dash-search" for="servicesDashboardSearch">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input
                            type="search"
                            id="servicesDashboardSearch"
                            placeholder="{{ __('doctors_directory_admin.dashboard_search_placeholder') }}"
                            autocomplete="off"
                            enterkeyhint="search"
                        >
                    </label>
                @endif
            </div>
            <div class="hs-dash-hero-art" aria-hidden="true"></div>
        </section>

        <div class="dda-stat-grid">
            <a href="{{ route('modules.doctors-admin.specialities.index') }}" class="dda-stat-card dda-stat-card--primary">
                <div class="dda-stat-card__body">
                    <p class="dda-stat-card__label">{{ __('doctors_directory_admin.stats.total') }}</p>
                    <p class="dda-stat-card__value">{{ $summary['total'] }}</p>
                </div>
                <span class="dda-stat-card__icon" aria-hidden="true"><i class="bi bi-diagram-3"></i></span>
            </a>

            <a href="{{ route('modules.doctors-admin.specialities.index', ['publish' => '1']) }}" class="dda-stat-card dda-stat-card--success">
                <div class="dda-stat-card__body">
                    <p class="dda-stat-card__label">{{ __('doctors_directory_admin.stats.published') }}</p>
                    <p class="dda-stat-card__value">{{ $summary['published'] }}</p>
                </div>
                <span class="dda-stat-card__icon" aria-hidden="true"><i class="bi bi-check-circle"></i></span>
            </a>

            <a href="{{ route('modules.doctors-admin.specialities.index', ['publish' => '0']) }}" class="dda-stat-card dda-stat-card--muted">
                <div class="dda-stat-card__body">
                    <p class="dda-stat-card__label">{{ __('doctors_directory_admin.stats.unpublished') }}</p>
                    <p class="dda-stat-card__value">{{ $summary['unpublished'] }}</p>
                </div>
                <span class="dda-stat-card__icon" aria-hidden="true"><i class="bi bi-eye-slash"></i></span>
            </a>
        </div>

        @if (count($cards) > 0)
            <div class="hs-dash-grid" id="servicesDashboardGrid">
                @foreach ($cards as $card)
                    @include('doctors-directory-admin.partials.dda-dash-card', [
                        'title' => $card->title,
                        'url' => $card->url,
                        'description' => $card->description,
                        'icon' => $card->icon,
                        'searchText' => strtolower($card->title.' '.$card->description),
                    ])
                @endforeach
            </div>
        @endif

        <div class="dda-footer-actions">
            <a href="{{ route('modules.doctors-admin.specialities.index') }}" class="hs-btn hs-btn--primary">
                <i class="bi bi-list-ul" aria-hidden="true"></i>
                {{ __('doctors_directory_admin.manage_specialities') }}
            </a>
            <a href="{{ route('modules.doctors.specialities.index') }}" class="hs-btn hs-btn--ghost" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                {{ __('doctors_directory_admin.view_public_directory') }}
            </a>
        </div>
    </div>
@endsection
