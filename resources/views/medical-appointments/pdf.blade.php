@php
    $isRtl = app()->getLocale() === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; direction: {{ $isRtl ? 'rtl' : 'ltr' }}; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #999; padding: 6px; vertical-align: top; }
    </style>
</head>
<body>
    <h1>{{ __('medical_appointments.title') }}</h1>
    <p>{{ __('medical_appointments.details') }} #{{ $appointment->id }}</p>
    <p>
        @if($document === 'request')
            {{ __('medical_appointments.request_pdf') }}
        @elseif($document === 'patient-accepted')
            {{ __('medical_appointments.patient_accept_pdf') }}
        @elseif($document === 'patient-rejected')
            {{ __('medical_appointments.patient_reject_pdf') }}
        @else
            {{ __('medical_appointments.doctor_reply_pdf') }}
        @endif
    </p>
    <table>
        <tr><th>{{ __('medical_appointments.patient_name') }}</th><td>{{ $appointment->localizedPatientName() }}</td></tr>
        <tr><th>{{ __('medical_appointments.file_number') }}</th><td>{{ $appointment->file_number }}</td></tr>
        <tr><th>{{ __('medical_appointments.doctor') }}</th><td>{{ $appointment->physicianRecord?->localizedDisplayName() }}</td></tr>
        <tr><th>{{ __('medical_appointments.department') }}</th><td>{{ $appointment->departmentRecord?->localizedName() }}</td></tr>
        <tr><th>{{ __('medical_appointments.procedure_place') }}</th><td>{{ $appointment->procedurePlaceRecord?->localizedName() }}</td></tr>
        <tr><th>{{ __('medical_appointments.status') }}</th><td>{{ $appointment->statusRecord?->localizedName() }}</td></tr>
    </table>
    @if($document === 'request' && $times->isNotEmpty())
        <h2>{{ __('medical_appointments.dates') }}</h2>
        <table>
            <tr><th>{{ __('medical_appointments.from') }}</th><th>{{ __('medical_appointments.status') }}</th></tr>
            @foreach($times as $time)
                @php($label = app(\App\Services\MedicalAppointment\MedicalAppointmentService::class)->timeLabel($time->date))
                <tr><td>{{ $label['date'] }} {{ $label['time'] }}</td><td>{{ $label['day'] }}</td></tr>
            @endforeach
        </table>
    @elseif($document === 'patient-accepted' && filled($appointment->patient_confirm_date))
        @php($label = app(\App\Services\MedicalAppointment\MedicalAppointmentService::class)->timeLabel(date('Y-m-d H:i', $appointment->patient_confirm_date)))
        <p>{{ $label['date'] }} {{ $label['time'] }}</p>
    @elseif($document === 'patient-rejected' && filled($appointment->patient_confirm_date_notice))
        <p>{{ app(\App\Services\MedicalAppointment\MedicalAppointmentService::class)->patientReasonName((int) $appointment->patient_confirm_date_notice) }}</p>
    @elseif($document === 'doctor-reply' && (int) $appointment->doctor_action > 0)
        <p>{{ (int) $appointment->doctor_action === 1 ? __('medical_appointments.agree') : __('medical_appointments.reschedule') }}</p>
    @endif
</body>
</html>
