@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-hospital-services.css') }}?v={{ filemtime(public_path('css/hm-hospital-services.css')) }}" rel="stylesheet">
@endpush

@section('title', __('hospital_services.pages.rooms.title'))

@section('sidebar_heading', __('hospital_services.title'))
@section('sidebar_subheading', __('hospital_services.pages.rooms.subtitle'))

@section('content')
    <div class="hm-hs hm-hs--rooms">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('hospital_services.title'), 'url' => route('modules.hospital-services')],
                ['label' => __('hospital_services.pages.rooms.title'), 'chip' => true],
            ],
        ])

        <section class="hs-page-hero" aria-labelledby="roomsPageTitle">
            <div>
                <h1 id="roomsPageTitle">{{ __('hospital_services.pages.rooms.title') }}</h1>
                <p>{{ __('hospital_services.pages.rooms.subtitle') }}</p>
            </div>
            <div class="hs-page-hero-art" aria-hidden="true"></div>
        </section>

        <div class="hs-list-search">
            <form method="GET" action="{{ route('modules.services.rooms.index') }}" class="hm-search-form">
                <div class="hm-search-field">
                    <i class="bi bi-search hm-search-field__icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        class="hm-search-field__input"
                        placeholder="{{ __('hospital_services.search_placeholder') }}"
                        maxlength="100"
                    >
                </div>
                <div class="hm-search-form__actions">
                    <button type="submit" class="btn hm-btn hm-btn--primary">{{ __('hospital_services.search') }}</button>
                    @if ($hasFilters)
                        <a href="{{ route('modules.services.rooms.index') }}" class="btn hm-btn hm-btn--outline">{{ __('hospital_services.reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="hs-list-panel">
            @if ($rooms->count() > 0)
                <div class="hm-services-table-wrap mb-0">
                    <table class="hm-services-table">
                        <thead>
                            <tr>
                                <th>{{ __('hospital_services.columns.code') }}</th>
                                <th>{{ __('hospital_services.columns.name') }}</th>
                                <th>{{ __('hospital_services.columns.price') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rooms as $room)
                                <tr>
                                    <td>{{ $room->code }}</td>
                                    <td>{{ $room->localizedName() }}</td>
                                    <td>{{ $room->formattedPrice() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $rooms->links('pagination.hm') }}
                </div>
            @else
                <div class="hm-empty-state">
                    <i class="bi bi-door-closed" aria-hidden="true"></i>
                    <p class="mb-0">{{ $hasFilters ? __('hospital_services.no_results') : __('hospital_services.no_rooms') }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
