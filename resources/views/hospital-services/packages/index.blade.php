@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-hospital-services.css') }}?v={{ filemtime(public_path('css/hm-hospital-services.css')) }}" rel="stylesheet">
@endpush

@section('title', __('hospital_services.pages.'.$pageKey.'.title'))

@section('sidebar_heading', __('hospital_services.title'))
@section('sidebar_subheading', __('hospital_services.section_page_subtitle'))

@section('content')
    @php
        $routeMap = [
            'lab' => 'modules.services.lab.index',
            'radiology' => 'modules.services.radiology.index',
            'agreements' => 'modules.services.agreements.index',
        ];
        $listRoute = $routeMap[$pageKey] ?? 'modules.hospital-services';
        $isAgreements = $pageKey === 'agreements';
        $pageTitle = __('hospital_services.pages.'.$pageKey.'.title');
    @endphp

    <div class="hm-hs hm-hs--packages">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('hospital_services.title'), 'url' => route('modules.hospital-services')],
                ['label' => $pageTitle, 'chip' => true],
            ],
        ])

        <section class="hs-page-hero" aria-labelledby="packagesPageTitle">
            <div>
                <h1 id="packagesPageTitle">{{ $pageTitle }}</h1>
                <p>{{ __('hospital_services.pages.'.$pageKey.'.subtitle') }}</p>
            </div>
            <div class="hs-page-hero-art" aria-hidden="true"></div>
        </section>

        <div class="hs-list-search">
            <form method="GET" action="{{ route($listRoute) }}" class="hm-search-form">
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
                        <a href="{{ route($listRoute) }}" class="btn hm-btn hm-btn--outline">{{ __('hospital_services.reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="hs-list-panel">
            @if ($packages->count() > 0)
                <div class="hm-services-table-wrap mb-0">
                    <table class="hm-services-table {{ $isAgreements ? 'hm-services-table--agreements' : '' }}">
                        <thead>
                            <tr>
                                <th>{{ __('hospital_services.columns.code') }}</th>
                                <th>{{ __('hospital_services.columns.name') }}</th>
                                @if ($isAgreements)
                                    <th>{{ __('hospital_services.columns.consultation') }}</th>
                                    <th>{{ __('hospital_services.columns.lab_radiology') }}</th>
                                    <th>{{ __('hospital_services.columns.operations') }}</th>
                                    <th>{{ __('hospital_services.columns.delivery') }}</th>
                                @else
                                    <th>{{ __('hospital_services.columns.price') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($packages as $package)
                                <tr>
                                    <td>{{ $package->code1 ?: '—' }}</td>
                                    <td>{{ $package->localizedName() }}</td>
                                    @if ($isAgreements)
                                        <td>{{ $package->discountValue($package->consultation_discount) }}</td>
                                        <td>{{ $package->discountValue($package->lab_x_rays_discount) }}</td>
                                        <td>{{ $package->discountValue($package->operations_hypnosis_discount) }}</td>
                                        <td>{{ $package->discountValue($package->delivery_discount) }}</td>
                                    @else
                                        <td>{{ $package->formattedPrice() }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $packages->links('pagination.hm') }}
                </div>
            @else
                <div class="hm-empty-state">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <p class="mb-0">{{ $hasFilters ? __('hospital_services.no_results') : __('hospital_services.no_services') }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
