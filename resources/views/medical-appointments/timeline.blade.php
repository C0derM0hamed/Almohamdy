@extends('layouts.app')
@section('title', __('medical_appointments.timeline'))
@section('sidebar_heading', __('medical_appointments.title'))
@section('sidebar_subheading', __('medical_appointments.subtitle'))
@section('content')
<div class="hm-module-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">{{ __('medical_appointments.timeline') }} #{{ $appointment->id }}</h1>
        <a class="btn btn-outline-secondary" href="{{ route($routes['show'], $appointment->id) }}">{{ __('medical_appointments.back') }}</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <strong>{{ __('medical_appointments.patient_name') }}:</strong> {{ $appointment->localizedPatientName() }}<br>
            <strong>{{ __('medical_appointments.file_number') }}:</strong> {{ $appointment->file_number }}<br>
            <strong>{{ __('medical_appointments.status') }}:</strong> {{ $appointment->statusRecord?->localizedName() }}
        </div>
    </div>

    <div class="vstack gap-3">
        @foreach($timeline as $item)
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $item->status?->localizedName() ?? $item->status_id }}</strong>
                        <time>{{ $item->date }}</time>
                    </div>
                    @if(filled($item->notice))
                        <p class="mt-2 mb-0">{{ $item->notice }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
