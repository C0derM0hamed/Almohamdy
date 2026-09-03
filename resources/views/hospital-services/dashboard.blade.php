@extends('layouts.app')

@section('title', __('hospital_services.dashboard'))

@section('sidebar_heading', __('hospital_services.title'))
@section('sidebar_subheading', __('hospital_services.dashboard_subtitle'))
@section('figma_page_header', true)

@push('scripts')
    <script src="{{ asset('js/hm-services-dashboard-search.js') }}?v={{ filemtime(public_path('js/hm-services-dashboard-search.js')) }}" defer></script>
@endpush

@section('content')
    <div class="hm-fm hm-hs hm-hs--dashboard">
        @include('layouts.partials.figma-module-header', [
            'title' => __('hospital_services.all_services'),
            'subtitle' => __('hospital_services.dashboard_subtitle'),
            'heroIconSrc' => asset('images/figma/services/hero.svg'),
            'heroIconSize' => 32,
            'crumbs' => [
                ['label' => __('dashboard.modules')],
                ['label' => __('hospital_services.all_services')],
            ],
        ])

        @if (count($sections) > 0)
            <section class="fm-search" aria-labelledby="servicesDashboardSearchTitle">
                <div class="fm-search__head">
                    <h2 id="servicesDashboardSearchTitle">{{ __('hospital_services.search_title') }}</h2>
                </div>
                <div class="fm-search__row">
                    <label class="fm-search__query">
                        <img src="{{ asset('images/figma/system/search.svg') }}" alt="" width="18" height="18">
                        <input
                            type="search"
                            id="servicesDashboardSearch"
                            placeholder="{{ __('hospital_services.dashboard_search_placeholder') }}"
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
                @include('layouts.partials.figma.section-head', [
                    'title' => __('hospital_services.all_services'),
                    'countLabel' => __('hospital_services.services_count', ['count' => count($sections)]),
                    'iconHtml' => '<img src="'.e(asset('images/figma/services/card-hospital.svg')).'" alt="" width="22" height="22">',
                ])

                <div class="fm-grid" id="servicesDashboardGrid">
                    @foreach ($sections as $card)
                        @php
                            $section = $card['section'];
                            $count = $card['count'];
                            $children = $card['children'] ?? [];
                            $sectionId = (int) $section->id;
                            $description = \App\Support\HospitalServices\SectionNavPresentation::descriptionFor($sectionId);
                            $label = $section->legacyNavName();
                            $countLabel = $count > 0 ? __('hospital_services.services_count', ['count' => $count]) : '';
                            $iconSrc = \App\Support\HospitalServices\SectionNavPresentation::figmaCardIcon($sectionId);
                        @endphp

                        @if ($children !== [])
                            <div
                                class="hs-dash-group"
                                data-hs-dash-card
                                data-search-text="{{ strtolower($label.' '.$description) }}"
                            >
                                @include('layouts.partials.figma-action-card', [
                                    'title' => $label,
                                    'url' => '',
                                    'description' => $description,
                                    'countLabel' => $countLabel,
                                    'iconSrc' => $iconSrc,
                                    'actionLabel' => __('hospital_services.view_services'),
                                    'titleTag' => 'h2',
                                ])
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
                            @include('layouts.partials.figma-action-card', [
                                'title' => $label,
                                'url' => route('modules.services.sections.show', $section->id),
                                'description' => $description,
                                'countLabel' => $countLabel,
                                'iconSrc' => $iconSrc,
                                'actionLabel' => __('hospital_services.view_services'),
                                'searchText' => strtolower($label.' '.$description),
                                'titleTag' => 'h2',
                            ])
                        @endif
                    @endforeach
                </div>
            </section>
        @else
            <div class="fm-empty">
                <p class="mb-0">{{ __('hospital_services.no_services') }}</p>
            </div>
        @endif
    </div>
@endsection
