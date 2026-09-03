@extends('layouts.app')
@section('title', __('medical_appointments.details'))
@section('sidebar_heading', __('medical_appointments.title'))
@section('sidebar_subheading', __('medical_appointments.subtitle'))
@push('styles')
    <link href="{{ asset('css/hm-doctors-redesign.css') }}?v={{ filemtime(public_path('css/hm-doctors-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-doctors-directory.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-medical-appointments.css') }}?v={{ filemtime(public_path('css/hm-medical-appointments.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-dd hm-dd--medical-appointments hm-medical-appointments hm-ma-detail">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <nav aria-label="{{ __('breadcrumbs.aria_label') }}" class="dd-breadcrumb dd-breadcrumb--bar">
        <a href="{{ route('dashboard') }}">{{ __('dashboard.title') }}</a>
        <span class="dd-breadcrumb-sep" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m9 18 6-6-6-6"/></svg></span>
        <a href="{{ route($routes['index']) }}">{{ __('medical_appointments.title') }}</a>
        <span class="dd-breadcrumb-sep" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m9 18 6-6-6-6"/></svg></span>
        <span class="dd-chip">{{ __('medical_appointments.details') }} #{{ $appointment->id }}</span>
    </nav>

    <section class="dd-hero hm-ma-hero">
        <div class="dd-hero-info">
            <div class="dd-hero-icon" aria-hidden="true"><i class="bi bi-calendar2-check"></i></div>
            <div>
                <h1>{{ __('medical_appointments.details') }} #{{ $appointment->id }}</h1>
                <p>{{ $appointment->statusRecord?->localizedName() ?? '—' }}</p>
            </div>
        </div>
        <a class="dd-btn dd-btn-outline" href="{{ route($routes['index']) }}"><i class="bi bi-arrow-right"></i> {{ __('medical_appointments.back') }}</a>
    </section>

    <div class="hm-ma-detail-grid">
        <section class="dd-doctor-card hm-ma-info-card">
            <div class="dd-section-head">
                <div class="dd-section-icon" aria-hidden="true"><i class="bi bi-person-vcard"></i></div>
                <h2>{{ __('medical_appointments.details') }}</h2>
            </div>
            <dl class="hm-ma-detail-list">
                <div><dt>{{ __('medical_appointments.patient_name') }}</dt><dd>{{ $appointment->localizedPatientName() }}</dd></div>
                <div><dt>{{ __('medical_appointments.file_number') }}</dt><dd>{{ $appointment->file_number ?: '—' }}</dd></div>
                <div><dt>{{ __('medical_appointments.doctor') }}</dt><dd>{{ $appointment->physicianRecord?->localizedDisplayName() ?? '—' }}</dd></div>
                <div><dt>{{ __('medical_appointments.department') }}</dt><dd>{{ $appointment->departmentRecord?->localizedName() ?? '—' }}</dd></div>
                <div><dt>{{ __('medical_appointments.procedure_place') }}</dt><dd>{{ $appointment->procedurePlaceRecord?->localizedName() ?? '—' }}</dd></div>
                <div><dt>{{ __('medical_appointments.coverage_status') }}</dt><dd>{{ $appointment->coverageStatusRecord?->localizedName() ?? '—' }}</dd></div>
                <div><dt>{{ __('medical_appointments.procedure_type') }}</dt><dd>{{ $appointment->localizedProcedureType() ?: '—' }}</dd></div>
                <div><dt>{{ __('medical_appointments.procedure_duration') }}</dt><dd>{{ $appointment->localizedProcedureDuration() ?: '—' }}</dd></div>
                <div><dt>{{ __('medical_appointments.status') }}</dt><dd><span class="badge">{{ $appointment->statusRecord?->localizedName() ?? '—' }}</span></dd></div>
            </dl>
        </section>

        <section class="dd-doctor-card hm-ma-action-card">
            <div class="dd-section-head">
                <div class="dd-section-icon" aria-hidden="true"><i class="bi bi-arrow-repeat"></i></div>
                <h2>{{ __('medical_appointments.update_status') }}</h2>
            </div>
            <div class="hm-ma-action-body">
                <form method="POST" action="{{ route($routes['status'], $appointment->id) }}">
                    @csrf
                    <div class="dd-form-field">
                        <label class="dd-red" for="medicalStatus">{{ __('medical_appointments.status') }}</label>
                        <select class="dd-form-select medical-status" data-target="detail" name="status_id" id="medicalStatus" required>
                            <option value="">—</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status->id }}">{{ $status->localizedName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="detailDate" class="dd-form-field" hidden>
                        <label class="dd-red" for="detailDateInput">{{ __('medical_appointments.choose_date') }}</label>
                        <input id="detailDateInput" type="datetime-local" name="date" class="dd-input">
                    </div>
                    <div id="detailReason" class="dd-form-field" hidden>
                        <label class="dd-red" for="detailReasonInput">{{ __('medical_appointments.reason') }}</label>
                        <input id="detailReasonInput" type="text" name="cleint_cancel_reason" class="dd-input" maxlength="200">
                    </div>
                    <button class="dd-btn dd-btn-primary" type="submit">{{ __('medical_appointments.save') }}</button>
                </form>
            </div>
        </section>
    </div>

    <section class="dd-doctor-card hm-ma-documents-card">
        <div class="dd-section-head">
            <div class="dd-section-icon" aria-hidden="true"><i class="bi bi-folder2-open"></i></div>
            <h2>{{ __('medical_appointments.documents') }}</h2>
        </div>
        <div class="hm-ma-document-actions">
            <a class="dd-btn dd-btn-outline" target="_blank" href="{{ route($routes['document'], [$appointment->id, 'request']) }}">{{ __('medical_appointments.request_pdf') }}</a>
            @if($appointment->patient_confirm_date)
                <a class="dd-btn dd-btn-outline" target="_blank" href="{{ route($routes['document'], [$appointment->id, 'patient-accepted']) }}">{{ __('medical_appointments.patient_accept_pdf') }}</a>
            @endif
            @if($appointment->patient_confirm_date_notice)
                <a class="dd-btn dd-btn-outline" target="_blank" href="{{ route($routes['document'], [$appointment->id, 'patient-rejected']) }}">{{ __('medical_appointments.patient_reject_pdf') }}</a>
            @endif
            @if((int) $appointment->doctor_action > 0)
                <a class="dd-btn dd-btn-outline" target="_blank" href="{{ route($routes['document'], [$appointment->id, 'doctor-reply']) }}">{{ __('medical_appointments.doctor_reply_pdf') }}</a>
            @endif
            <a class="dd-btn dd-btn-primary" href="{{ route($routes['timeline'], $appointment->id) }}">{{ __('medical_appointments.timeline') }}</a>
        </div>
    </section>
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
