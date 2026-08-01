@extends('layouts.app')
@section('title', __('medical_appointments.details'))
@section('sidebar_heading', __('medical_appointments.title'))
@section('sidebar_subheading', __('medical_appointments.subtitle'))
@section('content')
<div class="hm-module-page">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ __('medical_appointments.details') }} #{{ $appointment->id }}</h1>
            <span class="badge bg-primary">{{ $appointment->statusRecord?->localizedName() }}</span>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route($routes['index']) }}">{{ __('medical_appointments.back') }}</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('medical_appointments.patient_name') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->localizedPatientName() }}</dd>
                        <dt class="col-sm-4">{{ __('medical_appointments.file_number') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->file_number }}</dd>
                        <dt class="col-sm-4">{{ __('medical_appointments.doctor') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->physicianRecord?->localizedDisplayName() }}</dd>
                        <dt class="col-sm-4">{{ __('medical_appointments.department') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->departmentRecord?->localizedName() }}</dd>
                        <dt class="col-sm-4">{{ __('medical_appointments.procedure_place') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->procedurePlaceRecord?->localizedName() }}</dd>
                        <dt class="col-sm-4">{{ __('medical_appointments.coverage_status') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->coverageStatusRecord?->localizedName() }}</dd>
                        <dt class="col-sm-4">{{ __('medical_appointments.procedure_type') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->localizedProcedureType() }}</dd>
                        <dt class="col-sm-4">{{ __('medical_appointments.procedure_duration') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->localizedProcedureDuration() }}</dd>
                        <dt class="col-sm-4">{{ __('medical_appointments.status') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->statusRecord?->localizedName() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">{{ __('medical_appointments.update_status') }}</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route($routes['status'], $appointment->id) }}">
                        @csrf
                        <label class="form-label">{{ __('medical_appointments.status') }}</label>
                        <select class="form-select mb-3 medical-status" data-target="detail" name="status_id" id="medicalStatus" required>
                            <option value="">—</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status->id }}">{{ $status->localizedName() }}</option>
                            @endforeach
                        </select>
                        <div id="detailDate" hidden>
                            <label class="form-label">{{ __('medical_appointments.choose_date') }}</label>
                            <input type="datetime-local" name="date" class="form-control mb-3">
                        </div>
                        <div id="detailReason" hidden>
                            <label class="form-label">{{ __('medical_appointments.reason') }}</label>
                            <input type="text" name="cleint_cancel_reason" class="form-control mb-3" maxlength="200">
                        </div>
                        <button class="btn btn-primary">{{ __('medical_appointments.save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header"><h2 class="h5 mb-0">{{ __('medical_appointments.documents') }}</h2></div>
        <div class="card-body d-flex flex-wrap gap-2">
            <a class="btn btn-outline-dark" target="_blank" href="{{ route($routes['document'], [$appointment->id, 'request']) }}">{{ __('medical_appointments.request_pdf') }}</a>
            @if($appointment->patient_confirm_date)
                <a class="btn btn-outline-dark" target="_blank" href="{{ route($routes['document'], [$appointment->id, 'patient-accepted']) }}">{{ __('medical_appointments.patient_accept_pdf') }}</a>
            @endif
            @if($appointment->patient_confirm_date_notice)
                <a class="btn btn-outline-dark" target="_blank" href="{{ route($routes['document'], [$appointment->id, 'patient-rejected']) }}">{{ __('medical_appointments.patient_reject_pdf') }}</a>
            @endif
            @if((int) $appointment->doctor_action > 0)
                <a class="btn btn-outline-dark" target="_blank" href="{{ route($routes['document'], [$appointment->id, 'doctor-reply']) }}">{{ __('medical_appointments.doctor_reply_pdf') }}</a>
            @endif
            <a class="btn btn-outline-primary" href="{{ route($routes['timeline'], $appointment->id) }}">{{ __('medical_appointments.timeline') }}</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('medicalStatus')?.addEventListener('change', function () {
    document.getElementById('detailDate').hidden = this.value !== '5';
    document.getElementById('detailReason').hidden = this.value !== '12';
});
</script>
@endpush
