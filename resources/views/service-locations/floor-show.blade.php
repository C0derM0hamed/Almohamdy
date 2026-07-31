@extends('layouts.app')

@section('title', $label)

@section('sidebar_heading', $label)
@section('sidebar_subheading', __('service_locations.floor_show_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-service-locations.css') }}?v={{ filemtime(public_path('css/hm-service-locations.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-hs">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('service_locations.title'), 'url' => route('modules.service-locations.index')],
                ['label' => __('service_locations.floors_title'), 'url' => route('modules.service-locations.floors')],
                ['label' => $label, 'chip' => true],
            ],
        ])

        <section class="hs-page-hero" aria-labelledby="floorShowTitle">
            <div>
                <h1 id="floorShowTitle">{{ $label }}</h1>
                <p>{{ __('service_locations.floor_show_subtitle') }}</p>
            </div>
            <div class="hs-page-hero-art" aria-hidden="true"></div>
        </section>

        @if (count($items) > 0)
            <div class="hs-list-panel">
                <table class="hm-opd-info-table hm-floor-services-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('service_locations.service_name') }}</th>
                            <th scope="col">{{ __('service_locations.working_days') }}</th>
                            <th scope="col">{{ __('service_locations.working_hours') }}</th>
                            <th scope="col">{{ __('service_locations.visit_time') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td class="hm-floor-services-table__name">
                                    @if ($item['opdUrl'])
                                        <a href="{{ $item['opdUrl'] }}">{{ $item['sectionName'] }}</a>
                                    @else
                                        {{ $item['sectionName'] }}
                                    @endif
                                </td>
                                <td>{{ $item['dutyDays'] }}</td>
                                <td>{{ $item['dutyTime'] }}</td>
                                <td>{{ $item['visitTime'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="hs-empty">
                <i class="bi bi-layers" aria-hidden="true"></i>
                <p class="mb-0">{{ __('service_locations.no_floor_services') }}</p>
            </div>
        @endif

        <div class="sl-page-actions">
            <a href="{{ route('modules.service-locations.floors') }}" class="hs-btn hs-btn--ghost">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                {{ __('service_locations.back_to_floors') }}
            </a>
        </div>
    </div>
@endsection
