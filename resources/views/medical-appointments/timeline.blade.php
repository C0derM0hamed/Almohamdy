@extends('layouts.app')
@section('title', __('medical_appointments.timeline'))
@section('sidebar_heading', __('medical_appointments.title'))
@section('sidebar_subheading', __('medical_appointments.subtitle'))
@push('styles')
<link href="{{ asset('css/hm-doctors-redesign.css') }}?v={{ filemtime(public_path('css/hm-doctors-redesign.css')) }}" rel="stylesheet">
<link href="{{ asset('css/hm-doctors-directory.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory.css')) }}" rel="stylesheet">
<link href="{{ asset('css/hm-medical-appointments.css') }}?v={{ filemtime(public_path('css/hm-medical-appointments.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-dd hm-dd--medical-appointments hm-medical-appointments hm-ma-timeline">
    <nav aria-label="{{ __('breadcrumbs.aria_label') }}" class="dd-breadcrumb dd-breadcrumb--bar">
        <a href="{{ route('dashboard') }}">{{ __('dashboard.title') }}</a>
        <span class="dd-breadcrumb-sep" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m9 18 6-6-6-6"/></svg></span>
        <a href="{{ route($routes['index']) }}">{{ __('medical_appointments.title') }}</a>
        <span class="dd-breadcrumb-sep" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m9 18 6-6-6-6"/></svg></span>
        <span class="dd-chip">{{ __('medical_appointments.timeline') }} #{{ $appointment->id }}</span>
    </nav>

    <section class="dd-hero hm-ma-hero">
        <div class="dd-hero-info">
            <div class="dd-hero-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></div>
            <div>
                <h1>{{ __('medical_appointments.timeline') }} #{{ $appointment->id }}</h1>
                <p>{{ __('medical_appointments.subtitle') }}</p>
            </div>
        </div>
        <a class="dd-btn dd-btn-outline" href="{{ route($routes['show'], $appointment->id) }}"><i class="bi bi-arrow-right"></i> {{ __('medical_appointments.back') }}</a>
    </section>

    <div class="dd-stats hm-ma-timeline-stats">
        <article class="dd-stat"><span class="dd-stat-icon"><i class="bi bi-person"></i></span><div><small>{{ __('medical_appointments.patient_name') }}</small><b>{{ $appointment->localizedPatientName() }}</b><p>#{{ $appointment->id }}</p></div></article>
        <article class="dd-stat"><span class="dd-stat-icon"><i class="bi bi-folder2-open"></i></span><div><small>{{ __('medical_appointments.file_number') }}</small><b>{{ $appointment->file_number ?: '—' }}</b><p>{{ __('medical_appointments.timeline') }}</p></div></article>
        <article class="dd-stat"><span class="dd-stat-icon"><i class="bi bi-activity"></i></span><div><small>{{ __('medical_appointments.status') }}</small><b>{{ $appointment->statusRecord?->localizedName() ?? '—' }}</b><p>{{ __('medical_appointments.timeline') }}</p></div></article>
    </div>

    <section class="hm-ma-timeline-list">
        @foreach($timeline as $item)
            <div class="dd-doctor-card hm-ma-timeline-item">
                <div class="hm-ma-timeline-item__head">
                    <strong>{{ $item->status?->localizedName() ?? $item->status_id }}</strong>
                    <time>{{ $item->date }}</time>
                </div>
                @if(filled($item->notice))
                    <p>{{ $item->notice }}</p>
                @endif
            </div>
        @endforeach
    </section>
</div>
@endsection
