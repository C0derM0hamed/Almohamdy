@extends('layouts.app')

@section('title', __('doctors_directory.title'))

@section('sidebar_heading', __('doctors_directory.title'))
@section('sidebar_subheading', __('doctors_directory.subtitle'))
@section('figma_page_header', true)

@push('styles')
    <link href="{{ asset('css/hm-doctors-figma.css') }}?v={{ filemtime(public_path('css/hm-doctors-figma.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-dd hm-dd--figma">
        <header class="dd-figma-head">
            <div class="dd-figma-head__row">
                <div class="dd-figma-head__page">
                    <div class="hm-figma-crumb-row">
                        @include('layouts.partials.figma-sidebar-toggle')
                        @include('doctors-directory.partials.dd-breadcrumb', [
                            'variant' => 'plain',
                            'items' => [
                                ['label' => __('dashboard.modules')],
                                ['label' => __('doctors_directory.title'), 'chip' => true],
                            ],
                        ])
                    </div>

                    <div class="dd-figma-hero">
                        <div class="dd-figma-hero__icon" aria-hidden="true">
                            <img class="dd-figma-icon" src="{{ asset('images/figma/doctors/hero-stethoscope.svg') }}" alt="">
                        </div>
                        <div class="dd-figma-hero__copy">
                            <h1 id="clinicDoctorsHeroTitle">{{ __('doctors_directory.title') }}</h1>
                            <p>{{ __('doctors_directory.index_hero_subtitle') }}</p>
                        </div>
                    </div>
                </div>

                @include('layouts.partials.figma-header-tools')
            </div>
        </header>

        @if (count($cards) > 0)
            <div class="dd-figma-stats">
                <div class="dd-figma-stat dd-figma-stat--primary">
                    <div class="dd-figma-stat__icon" aria-hidden="true">
                        <img class="dd-figma-icon" src="{{ asset('images/figma/doctors/stat-specialties.svg') }}" alt="">
                    </div>
                    <div class="dd-figma-stat__copy">
                        <small>{{ __('doctors_directory.stat_total_specialties') }}</small>
                        <b>{{ $summary->totalSpecialities }}</b>
                        <p>{{ __('doctors_directory.stat_total_specialties_hint') }}</p>
                    </div>
                </div>
                <div class="dd-figma-stat dd-figma-stat--dark">
                    <div class="dd-figma-stat__icon" aria-hidden="true">
                        <img class="dd-figma-icon" src="{{ asset('images/figma/doctors/stat-doctors.svg') }}" alt="">
                    </div>
                    <div class="dd-figma-stat__copy">
                        <small>{{ __('doctors_directory.stat_total_doctors') }}</small>
                        <b>{{ $summary->totalDoctors }}</b>
                        <p>{{ __('doctors_directory.stat_total_doctors_hint') }}</p>
                    </div>
                </div>
                <div class="dd-figma-stat dd-figma-stat--primary dd-figma-stat--available">
                    <div class="dd-figma-stat__icon" aria-hidden="true">
                        <img class="dd-figma-icon" src="{{ asset('images/figma/doctors/stat-available.svg') }}" alt="">
                    </div>
                    <div class="dd-figma-stat__copy">
                        <small>{{ __('doctors_directory.stat_available_today') }}</small>
                        <b>{{ $summary->availableToday }}</b>
                        <p>{{ __('doctors_directory.stat_available_today_hint') }}</p>
                    </div>
                </div>
            </div>

            <section class="dd-figma-filters" aria-labelledby="clinicDoctorsFiltersTitle">
                <div class="dd-figma-filters__head">
                    <h2 id="clinicDoctorsFiltersTitle">{{ __('doctors_directory.filters_section_title') }}</h2>
                    <p>{{ __('doctors_directory.filters_section_subtitle') }}</p>
                </div>
                <div class="dd-figma-filters__row">
                    <label class="dd-figma-search">
                        <img class="dd-figma-icon" src="{{ asset('images/figma/doctors/search.svg') }}" alt="" aria-hidden="true">
                        <input
                            type="search"
                            id="specialityLiveSearch"
                            placeholder="{{ __('doctors_directory.index_search_placeholder') }}"
                            aria-label="{{ __('doctors_directory.index_search_placeholder') }}"
                            autocomplete="off"
                            enterkeyhint="search"
                        >
                    </label>

                    <label class="dd-figma-select">
                        <select id="specialitySortFilter" aria-label="{{ __('doctors_directory.filter_most_booked') }}">
                            <option value="most_booked">{{ __('doctors_directory.filter_most_booked') }}</option>
                            <option value="default">{{ __('doctors_directory.filter_sort_default') }}</option>
                        </select>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </label>

                    <label class="dd-figma-select">
                        <select id="specialityAvailabilityFilter" aria-label="{{ __('doctors_directory.filter_available_today') }}">
                            <option value="all">{{ __('doctors_directory.filter_available_today') }}</option>
                            <option value="today">{{ __('doctors_directory.filter_available_today_only') }}</option>
                        </select>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </label>

                    <button type="button" id="specialityApplyFilters" class="dd-figma-btn dd-figma-btn--search">
                        {{ __('doctors_directory.search') }}
                    </button>

                    <button type="button" id="specialityClearFilters" class="dd-figma-btn dd-figma-btn--reset">
                        {{ __('doctors_directory.reset') }}
                        <img class="dd-figma-icon" src="{{ asset('images/figma/doctors/reset.svg') }}" alt="" aria-hidden="true">
                    </button>
                </div>
            </section>

            <section class="dd-figma-section" aria-labelledby="clinicDoctorsGridTitle">
                <div class="dd-figma-section__head">
                    <div class="dd-figma-section__title">
                        <span class="dd-figma-section__icon" aria-hidden="true">
                            <img class="dd-figma-icon" src="{{ asset('images/figma/doctors/section-grid.svg') }}" alt="">
                        </span>
                        <h2 id="clinicDoctorsGridTitle">{{ __('doctors_directory.visible_specialties') }}</h2>
                    </div>
                    <span class="dd-figma-count" id="specialityVisibleCount">
                        <span id="specialityVisibleNumber">{{ count($cards) }}</span>
                        {{ trans_choice('doctors_directory.clinic_word', count($cards)) }}
                    </span>
                </div>

                <div
                    id="specialitySearchEmpty"
                    class="dd-figma-empty"
                    role="status"
                    aria-live="polite"
                    hidden
                >
                    {{ __('doctors_directory.no_specialities_search') }}
                </div>

                <div class="dd-figma-grid" id="specialityCardsGrid">
                    @foreach ($cards as $card)
                        <a
                            href="{{ $card->url }}"
                            class="dd-figma-card js-speciality-card"
                            data-name-ar="{{ $card->nameAr }}"
                            data-name-en="{{ $card->nameEn }}"
                            data-display-title="{{ $card->title }}"
                            data-doctor-count="{{ $card->doctorCount }}"
                            data-available-today="{{ $card->availableTodayCount }}"
                        >
                            <span class="dd-figma-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">{!! $card->iconSvg !!}</svg>
                            </span>
                            <h3 class="js-speciality-card-title">{{ $card->title }}</h3>
                            <span class="dd-figma-card__meta">
                                <span class="dd-figma-card__dot" aria-hidden="true"></span>
                                <span>{{ trans_choice('doctors_directory.doctors_count_label', $card->doctorCount, ['count' => $card->doctorCount]) }}</span>
                            </span>
                            <span class="dd-figma-card__action">
                                <img class="dd-figma-icon" src="{{ asset('images/figma/doctors/view-doctors.svg') }}" alt="">
                                {{ __('doctors_directory.view_doctors') }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @else
            <div class="dd-figma-empty">
                {{ __('doctors_directory.no_specialities') }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/hm-speciality-search.js') }}?v={{ filemtime(public_path('js/hm-speciality-search.js')) }}" defer></script>
@endpush
