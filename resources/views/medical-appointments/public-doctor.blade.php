@extends('layouts.public-reply')
@section('title', __('medical_appointments.doctor_result'))
@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h1 class="h4 mb-3">{{ __('medical_appointments.doctor_decision') }}</h1>
        <p class="text-muted mb-4">{{ $appointment->patient_name }} - {{ $appointment->file_number }}</p>

        <form method="POST" action="">
            @csrf
            <label class="form-check d-flex gap-3 align-items-center border rounded-3 p-3 mb-3">
                <input class="form-check-input" type="radio" name="doctor_action" value="1" required>
                <span>{{ __('medical_appointments.agree') }}</span>
            </label>
            <label class="form-check d-flex gap-3 align-items-center border rounded-3 p-3 mb-4">
                <input class="form-check-input" type="radio" name="doctor_action" value="2" required>
                <span>{{ __('medical_appointments.reschedule') }}</span>
            </label>
            <button class="btn btn-primary">{{ __('medical_appointments.save') }}</button>
        </form>
    </div>
</div>
@endsection
