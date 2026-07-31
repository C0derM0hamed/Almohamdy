@extends('layouts.app')

@section('title', __('doctors_directory.title'))

@section('sidebar_heading', __('doctors_directory.title'))
@section('sidebar_subheading', __('doctors_directory.subtitle'))

@php($isRtl = app()->getLocale() === 'ar')

@push('styles')
    <link href="{{ asset('css/hm-doctors-redesign.css') }}?v={{ filemtime(public_path('css/hm-doctors-redesign.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-dd hm-dd--index">
        @include('doctors-directory.partials.dd-breadcrumb', [
            'variant' => 'bar',
            'items' => [
                ['label' => __('doctors_directory.title'), 'chip' => true],
            ],
        ])

        <section class="dd-hero" aria-labelledby="clinicDoctorsHeroTitle">
            <div class="dd-hero-info">
                <div class="dd-hero-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M8 2h8v4H8z"/><path d="M6 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1"/><path d="M9 14h6M12 11v6"/></svg>
                </div>
                <div>
                    <h1 id="clinicDoctorsHeroTitle">{{ __('doctors_directory.title') }}</h1>
                    <p>{{ __('doctors_directory.index_hero_subtitle') }}</p>
                </div>
            </div>
            <div class="dd-hospital-art" aria-hidden="true"></div>
        </section>

        @if (count($cards) > 0)
            <div class="dd-filters">
                <label class="dd-field">
                    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input
                        type="search"
                        id="specialityLiveSearch"
                        placeholder="{{ __('doctors_directory.index_search_placeholder') }}"
                        aria-label="{{ __('doctors_directory.index_search_placeholder') }}"
                        autocomplete="off"
                        enterkeyhint="search"
                    >
                </label>

                <div class="dd-select-field">
                    <span class="dd-left">
                        <svg viewBox="0 0 24 24"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
                    </span>
                    <select id="specialitySortFilter" aria-label="{{ __('doctors_directory.filter_most_booked') }}">
                        <option value="most_booked">{{ __('doctors_directory.filter_most_booked') }}</option>
                        <option value="default">{{ __('doctors_directory.filter_sort_default') }}</option>
                    </select>
                    <svg viewBox="0 0 24 24" style="width:18px;height:18px"><path d="m6 9 6 6 6-6"/></svg>
                </div>

                <div class="dd-select-field">
                    <span class="dd-left">
                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
                    </span>
                    <select id="specialityAvailabilityFilter" aria-label="{{ __('doctors_directory.filter_available_today') }}">
                        <option value="all">{{ __('doctors_directory.filter_available_today') }}</option>
                        <option value="today">{{ __('doctors_directory.filter_available_today_only') }}</option>
                    </select>
                    <svg viewBox="0 0 24 24" style="width:18px;height:18px"><path d="m6 9 6 6 6-6"/></svg>
                </div>

                <button type="button" id="specialityClearFilters" class="dd-clear-btn">
                    <svg viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
                    {{ __('doctors_directory.clear_filters') }}
                </button>
            </div>

            <div class="dd-stats">
                <div class="dd-stat">
                    <div class="dd-stat-icon"><svg viewBox="0 0 24 24"><path d="M3 7h6l2 3h10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M12 14h6M15 11v6"/></svg></div>
                    <div>
                        <small>{{ __('doctors_directory.stat_total_specialties') }}</small>
                        <b>{{ $summary->totalSpecialities }}</b>
                        <p>{{ __('doctors_directory.stat_total_specialties_hint') }}</p>
                    </div>
                </div>

                <div class="dd-stat">
                    <div class="dd-stat-icon"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-8 0v2"/><circle cx="12" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/></svg></div>
                    <div>
                        <small>{{ __('doctors_directory.stat_total_doctors') }}</small>
                        <b>{{ $summary->totalDoctors }}</b>
                        <p>{{ __('doctors_directory.stat_total_doctors_hint') }}</p>
                    </div>
                </div>

                <div class="dd-stat dd-green">
                    <div class="dd-stat-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg></div>
                    <div>
                        <small>{{ __('doctors_directory.stat_available_today') }}</small>
                        <b>{{ $summary->availableToday }}</b>
                        <p>{{ __('doctors_directory.stat_available_today_hint') }}</p>
                    </div>
                </div>
            </div>

            <div
                id="specialitySearchEmpty"
                class="dd-empty"
                role="status"
                aria-live="polite"
                hidden
            >
                {{ __('doctors_directory.no_specialities_search') }}
            </div>

            <div class="dd-specialties" id="specialityCardsGrid">
                @foreach ($cards as $card)
                    <a
                        href="{{ $card->url }}"
                        class="dd-specialty-card js-speciality-card"
                        data-name-ar="{{ $card->nameAr }}"
                        data-name-en="{{ $card->nameEn }}"
                        data-display-title="{{ $card->title }}"
                        data-doctor-count="{{ $card->doctorCount }}"
                        data-available-today="{{ $card->availableTodayCount }}"
                    >
                        <span class="dd-specialty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">{!! $card->iconSvg !!}</svg>
                        </span>
                        <div>
                            <h3 class="js-speciality-card-title">{{ $card->title }}</h3>
                            <p>{{ trans_choice('doctors_directory.doctors_count_label', $card->doctorCount, ['count' => $card->doctorCount]) }}</p>
                            <span class="dd-view">
                                {{ __('doctors_directory.view_doctors') }} {{ $isRtl ? '←' : '→' }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="dd-empty">
                {{ __('doctors_directory.no_specialities') }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/hm-speciality-search.js') }}?v={{ filemtime(public_path('js/hm-speciality-search.js')) }}" defer></script>
@endpush
