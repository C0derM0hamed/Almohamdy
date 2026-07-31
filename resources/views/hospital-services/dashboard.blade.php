@extends('layouts.app')

@section('title', __('hospital_services.dashboard'))

@section('sidebar_heading', __('hospital_services.title'))
@section('sidebar_subheading', __('hospital_services.dashboard_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/hm-services-dashboard-search.js') }}?v={{ filemtime(public_path('js/hm-services-dashboard-search.js')) }}" defer></script>
@endpush

@section('content')
    <div class="hm-hs hm-hs--dashboard">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('hospital_services.title'), 'chip' => true],
            ],
        ])

        <section class="hs-dash-hero" aria-labelledby="servicesDashboardTitle">
            <div>
                <h1 id="servicesDashboardTitle">{{ __('hospital_services.dashboard') }}</h1>
                <p>{{ __('hospital_services.dashboard_subtitle') }}</p>

                @if (count($sections) > 0)
                    <label class="hs-dash-search" for="servicesDashboardSearch">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input
                            type="search"
                            id="servicesDashboardSearch"
                            placeholder="{{ __('hospital_services.dashboard_search_placeholder') }}"
                            autocomplete="off"
                            enterkeyhint="search"
                        >
                    </label>
                @endif
            </div>
            <div class="hs-dash-hero-art" aria-hidden="true"></div>
        </section>

        @if (count($sections) > 0)
            <div class="hs-dash-grid" id="servicesDashboardGrid">
                @foreach ($sections as $card)
                    @php
                        $section = $card['section'];
                        $count = $card['count'];
                        $children = $card['children'] ?? [];
                        $sectionId = (int) $section->id;
                        $description = \App\Support\HospitalServices\SectionNavPresentation::descriptionFor($sectionId);
                        $label = $section->legacyNavName();
                        $countLabel = __('hospital_services.services_count', ['count' => $count]);
                    @endphp

                    @if ($children !== [])
                        <div
                            class="hs-dash-group"
                            data-hs-dash-card
                            data-search-text="{{ strtolower($label.' '.$description) }}"
                        >
                            <div class="hs-dash-card{{ $count === 0 ? ' is-empty' : '' }}" tabindex="0" role="group" aria-label="{{ $label }}">
                                <div class="hs-dash-card__content">
                                    <div class="hs-dash-card__icon" aria-hidden="true">
                                        @include('hospital-services.partials.hs-icon', [
                                            'svg' => \App\Support\HospitalServices\ServiceIcon::sectionSvg($sectionId),
                                            'size' => 40,
                                        ])
                                    </div>
                                    <h2 class="hs-dash-card__title">{{ $label }}</h2>
                                    <span class="hs-dash-card__line" aria-hidden="true"></span>
                                    <p class="hs-dash-card__desc">{{ $description }}</p>
                                </div>
                                <div class="hs-dash-card__bottom">
                                    @if ($count > 0)
                                        <span class="hs-dash-card__count">{{ $countLabel }}</span>
                                    @else
                                        <span class="hs-dash-card__count">&nbsp;</span>
                                    @endif
                                    <span class="hs-dash-card__arrow" aria-hidden="true">
                                        <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-arrow-left' : 'bi-arrow-right' }}"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="hs-dash-group__flyout" role="menu" aria-label="{{ $label }}">
                                @foreach ($children as $childCard)
                                    @php
                                        $childSection = $childCard['section'];
                                        $childCount = $childCard['count'];
                                    @endphp
                                    <a
                                        href="{{ route('modules.services.sections.show', $childSection->id) }}"
                                        class="hs-dash-group__item{{ $childCount === 0 ? ' is-empty' : '' }}"
                                        role="menuitem"
                                    >
                                        <span>{{ $childSection->legacyNavName() }}</span>
                                        @if ($childCount > 0)
                                            <span>{{ __('hospital_services.services_count', ['count' => $childCount]) }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a
                            href="{{ route('modules.services.sections.show', $section->id) }}"
                            class="hs-dash-card{{ $count === 0 ? ' is-empty' : '' }}"
                            data-hs-dash-card
                            data-search-text="{{ strtolower($label.' '.$description) }}"
                        >
                            <div class="hs-dash-card__content">
                                <div class="hs-dash-card__icon" aria-hidden="true">
                                    @include('hospital-services.partials.hs-icon', [
                                        'svg' => \App\Support\HospitalServices\ServiceIcon::sectionSvg($sectionId),
                                        'size' => 40,
                                    ])
                                </div>
                                <h2 class="hs-dash-card__title">{{ $label }}</h2>
                                <span class="hs-dash-card__line" aria-hidden="true"></span>
                                <p class="hs-dash-card__desc">{{ $description }}</p>
                            </div>
                            <div class="hs-dash-card__bottom">
                                @if ($count > 0)
                                    <span class="hs-dash-card__count">{{ $countLabel }}</span>
                                @else
                                    <span class="hs-dash-card__count">&nbsp;</span>
                                @endif
                                <span class="hs-dash-card__arrow" aria-hidden="true">
                                    <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-arrow-left' : 'bi-arrow-right' }}"></i>
                                </span>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        @else
            <div class="hs-empty">
                <i class="bi bi-grid" aria-hidden="true"></i>
                <p class="mb-0">{{ __('hospital_services.no_services') }}</p>
            </div>
        @endif
    </div>
@endsection
