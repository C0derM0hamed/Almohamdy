@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-employee-services.css') }}?v={{ filemtime(public_path('css/hm-employee-services.css')) }}" rel="stylesheet">
@endpush

@section('title', $pageTitle)

@section('sidebar_heading', __('employee_services.title'))
@section('sidebar_subheading', __('dashboard.coming_soon'))

@section('content')
    <div class="hm-hs hm-es hm-es--placeholder">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('employee_services.title'), 'url' => route('modules.employee-services')],
                ['label' => $pageTitle, 'chip' => true],
            ],
        ])

        <section class="hs-page-hero" aria-labelledby="placeholderTitle">
            <div>
                <h1 id="placeholderTitle">{{ $pageTitle }}</h1>
                <p>{{ __('dashboard.coming_soon') }}</p>
            </div>
            <div class="hs-page-hero-art" aria-hidden="true"></div>
        </section>

        <div class="es-coming-soon">
            <div class="es-coming-soon__icon" aria-hidden="true">
                <i class="bi bi-tools"></i>
            </div>
            <h2 class="es-coming-soon__title">{{ $pageTitle }}</h2>
            <p class="es-coming-soon__text">{{ __('dashboard.coming_soon') }}</p>
            <a href="{{ route('modules.employee-services') }}" class="hs-btn hs-btn--ghost es-coming-soon__back">
                <i class="bi bi-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('employee_services.title') }}
            </a>
        </div>
    </div>
@endsection
