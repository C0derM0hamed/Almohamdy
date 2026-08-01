@extends('layouts.public-reply')
@section('title', __('medical_appointments.patient_result'))
@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h1 class="h4 mb-3">{{ __('medical_appointments.patient_response') }}</h1>
        <p class="text-muted">{{ __('medical_appointments.subtitle') }}</p>

        <form method="POST" action="">
            @csrf
            <div class="mb-4">
                <div class="fw-semibold mb-2">{{ __('medical_appointments.choose_date') }}</div>
                @foreach($times as $time)
                    @php($label = app(\App\Services\MedicalAppointment\MedicalAppointmentService::class)->timeLabel($time->date))
                    <label class="form-check d-flex gap-3 align-items-center border rounded-3 p-3 mb-2">
                        <input class="form-check-input" type="radio" name="patient_confirm_date" value="{{ strtotime($time->date) }}" required>
                        <span>{{ $label['date'] }} - {{ $label['day'] }} - {{ $label['time'] }}</span>
                    </label>
                @endforeach
            </div>

            <div class="mb-3">
                <div class="fw-semibold mb-2">{{ __('medical_appointments.reason') }}</div>
                @foreach($reasons as $reason)
                    <label class="form-check d-flex gap-3 align-items-center border rounded-3 p-3 mb-2">
                        <input class="form-check-input" type="radio" name="patient_confirm_date" value="{{ $reason->id }}" required>
                        <span>{{ $reason->localizedName() }}</span>
                    </label>
                @endforeach
            </div>

            <button class="btn btn-primary">{{ __('medical_appointments.save') }}</button>
        </form>
    </div>
</div>
@endsection
