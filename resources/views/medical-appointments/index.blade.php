@extends('layouts.app')
@section('title', __('medical_appointments.title'))
@section('sidebar_heading', __('medical_appointments.title'))
@section('sidebar_subheading', __('medical_appointments.subtitle'))
@push('styles')
    <link href="{{ asset('css/hm-doctors-redesign.css') }}?v={{ filemtime(public_path('css/hm-doctors-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-doctors-directory.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-medical-appointments.css') }}?v={{ filemtime(public_path('css/hm-medical-appointments.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-dd hm-dd--medical-appointments hm-medical-appointments" data-module="medical-appointments">
    <nav aria-label="{{ __('breadcrumbs.aria_label') }}" class="dd-breadcrumb dd-breadcrumb--bar">
        <a href="{{ route('dashboard') }}">{{ __('dashboard.title') }}</a>
        <span class="dd-breadcrumb-sep" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="m9 18 6-6-6-6"/></svg>
        </span>
        <span class="dd-chip">{{ __('medical_appointments.title') }}</span>
    </nav>

    <section class="dd-hero hm-ma-hero">
        <div class="dd-hero-info">
            <div class="dd-hero-icon" aria-hidden="true"><i class="bi bi-heart-pulse"></i></div>
            <div>
                <h1>{{ __('medical_appointments.title') }}</h1>
                <p>{{ __('medical_appointments.subtitle') }}</p>
            </div>
        </div>
        <button class="dd-btn dd-btn-primary hm-ma-create" data-bs-toggle="modal" data-bs-target="#medicalCreate">
            <i class="bi bi-plus-lg"></i> {{ __('medical_appointments.create') }}
        </button>
    </section>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="dd-stats hm-ma-status-grid" aria-label="{{ __('medical_appointments.status') }}">
        @foreach($statuses as $status)
            <div class="dd-stat hm-ma-status-card">
                <span class="dd-stat-icon hm-ma-status-icon"><i class="bi bi-heart-pulse"></i></span>
                <div>
                    <small>{{ $status->localizedName() }}</small>
                    <b class="hm-ma-status-value">{{ $summary[$status->id] ?? 0 }}</b>
                    <p>{{ __('medical_appointments.title') }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <section class="dd-search-card hm-ma-filter-card">
        <div class="dd-section-head">
            <div class="dd-section-icon" aria-hidden="true"><i class="bi bi-sliders2"></i></div>
            <h2>{{ __('medical_appointments.search') }}</h2>
        </div>
        <form method="GET">
            <div class="dd-search-grid hm-ma-filter-form">
                <div class="dd-form-field"><label class="dd-red" for="medicalFrom">{{ __('medical_appointments.from') }}</label><input id="medicalFrom" type="date" class="dd-input" name="from" value="{{ $filters['from'] ?? '' }}"></div>
                <div class="dd-form-field"><label class="dd-red" for="medicalTo">{{ __('medical_appointments.to') }}</label><input id="medicalTo" type="date" class="dd-input" name="to" value="{{ $filters['to'] ?? '' }}"></div>
                <div class="dd-form-field"><label class="dd-red" for="medicalStatus">{{ __('medical_appointments.status') }}</label><select id="medicalStatus" class="dd-form-select" name="status"><option value="">{{ __('medical_appointments.all') }}</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected((string)($filters['status'] ?? '') === (string)$status->id)>{{ $status->localizedName() }}</option>@endforeach</select></div>
                <div class="dd-form-field"><label class="dd-red" for="medicalMobile">{{ __('medical_appointments.mobile') }}</label><input id="medicalMobile" class="dd-input" name="mobile" value="{{ $filters['mobile'] ?? '' }}"></div>
                <div class="dd-form-field"><label class="dd-red" for="medicalDepartmentFilter">{{ __('medical_appointments.department') }}</label><select id="medicalDepartmentFilter" class="dd-form-select" name="department"><option value="">{{ __('medical_appointments.all') }}</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string)($filters['department'] ?? '') === (string)$department->id)>{{ $department->localizedName() }}</option>@endforeach</select></div>
                <div class="dd-form-field"><label class="dd-red" for="medicalPlaceFilter">{{ __('medical_appointments.procedure_place') }}</label><select id="medicalPlaceFilter" class="dd-form-select" name="procedure_place"><option value="">{{ __('medical_appointments.all') }}</option>@foreach($procedurePlaces as $place)<option value="{{ $place->id }}" @selected((string)($filters['procedure_place'] ?? '') === (string)$place->id)>{{ $place->localizedName() }}</option>@endforeach</select></div>
            </div>
            <div class="dd-search-actions">
                <button class="dd-btn dd-btn-primary" type="submit"><i class="bi bi-search"></i> {{ __('medical_appointments.search') }}</button>
                <a href="{{ route($routes['index']) }}" class="dd-btn dd-btn-outline"><i class="bi bi-arrow-counterclockwise"></i> {{ __('medical_appointments.reset') }}</a>
            </div>
        </form>
    </section>

    <section class="dd-doctor-card hm-ma-list-card">
        <div class="dd-section-head hm-ma-list-heading">
            <div class="dd-section-icon" aria-hidden="true"><i class="bi bi-calendar2-check"></i></div>
            <div><h2>{{ __('medical_appointments.title') }}</h2><p>{{ __('medical_appointments.subtitle') }}</p></div>
            <span class="hm-ma-list-count">{{ $appointments->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 hm-ma-table">
                <thead>
                    <tr>
                        <th>{{ __('medical_appointments.patient_name') }}</th>
                        <th>{{ __('medical_appointments.file_number') }}</th>
                        <th>{{ __('medical_appointments.doctor') }}</th>
                        <th>{{ __('medical_appointments.department') }}</th>
                        <th>{{ __('medical_appointments.procedure_place') }}</th>
                        <th>{{ __('medical_appointments.status') }}</th>
                        <th>{{ __('medical_appointments.update_status') }}</th>
                        <th>{{ __('medical_appointments.documents') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $appointment)
                        <tr>
                            <td><a href="{{ route($routes['show'], $appointment->id) }}">{{ $appointment->localizedPatientName() }}</a></td>
                            <td>{{ $appointment->file_number }}</td>
                            <td>{{ $appointment->physicianRecord?->localizedDisplayName() ?? '—' }}</td>
                            <td>{{ $appointment->departmentRecord?->localizedName() ?? '—' }}</td>
                            <td>{{ $appointment->procedurePlaceRecord?->localizedName() ?? '—' }}</td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $appointment->statusRecord?->localizedName() ?? $appointment->status }}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#statusModal{{ $appointment->id }}">{{ __('medical_appointments.update_status') }}</button>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bi bi-folder2-open"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" target="_blank" href="{{ route($routes['document'], [$appointment->id, 'request']) }}">{{ __('medical_appointments.request_pdf') }}</a>
                                        <a class="dropdown-item" href="{{ route($routes['show'], $appointment->id) }}">{{ __('medical_appointments.details') }}</a>
                                        @if($appointment->patient_confirm_date)
                                            <a class="dropdown-item" target="_blank" href="{{ route($routes['document'], [$appointment->id, 'patient-accepted']) }}">{{ __('medical_appointments.patient_accept_pdf') }}</a>
                                        @endif
                                        @if($appointment->patient_confirm_date_notice)
                                            <a class="dropdown-item" target="_blank" href="{{ route($routes['document'], [$appointment->id, 'patient-rejected']) }}">{{ __('medical_appointments.patient_reject_pdf') }}</a>
                                        @endif
                                        @if((int) $appointment->doctor_action > 0)
                                            <a class="dropdown-item" target="_blank" href="{{ route($routes['document'], [$appointment->id, 'doctor-reply']) }}">{{ __('medical_appointments.doctor_reply_pdf') }}</a>
                                        @endif
                                        <a class="dropdown-item" href="{{ route($routes['timeline'], $appointment->id) }}">{{ __('medical_appointments.timeline') }}</a>
                                        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#filesModal{{ $appointment->id }}">{{ __('medical_appointments.documents') }}</button>
                                    </div>
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr><td colspan="8" class="text-center py-5 text-muted">{{ __('medical_appointments.empty') ?? __('medical_appointments.all') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($appointments->hasPages())
            <div class="card-footer">{{ $appointments->links() }}</div>
        @endif
    </section>

    @push('modals')
    @foreach($appointments as $appointment)
        <div class="modal fade" id="statusModal{{ $appointment->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route($routes['status'], $appointment->id) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('medical_appointments.update_status') }} #{{ $appointment->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">{{ __('medical_appointments.status') }}</label>
                        <select class="form-select mb-3 medical-status" data-target="{{ $appointment->id }}" name="status_id" required>
                            <option value="">—</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status->id }}">{{ $status->localizedName() }}</option>
                            @endforeach
                        </select>
                        <div class="medical-status-date" id="statusDate{{ $appointment->id }}" hidden>
                            <label class="form-label">{{ __('medical_appointments.choose_date') }}</label>
                            <input type="datetime-local" class="form-control mb-3" name="date">
                        </div>
                        <div class="medical-status-reason" id="statusReason{{ $appointment->id }}" hidden>
                            <label class="form-label">{{ __('medical_appointments.reason') }}</label>
                            <input type="text" class="form-control mb-3" name="cleint_cancel_reason" maxlength="200">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">{{ __('medical_appointments.save') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="filesModal{{ $appointment->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('medical_appointments.documents') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered mb-0">
                            <tr><th>{{ __('medical_appointments.title') }}</th><th>{{ __('medical_appointments.documents') }}</th></tr>
                            <tr><td>{{ __('medical_appointments.request_pdf') }}</td><td><a target="_blank" href="{{ route($routes['document'], [$appointment->id, 'request']) }}" class="btn btn-link"><i class="bi bi-file-earmark-pdf"></i></a></td></tr>
                            @if($appointment->patient_confirm_date)
                                <tr><td>{{ __('medical_appointments.patient_accept_pdf') }}</td><td><a target="_blank" href="{{ route($routes['document'], [$appointment->id, 'patient-accepted']) }}" class="btn btn-link"><i class="bi bi-file-earmark-pdf"></i></a></td></tr>
                            @endif
                            @if($appointment->patient_confirm_date_notice)
                                <tr><td>{{ __('medical_appointments.patient_reject_pdf') }}</td><td><a target="_blank" href="{{ route($routes['document'], [$appointment->id, 'patient-rejected']) }}" class="btn btn-link"><i class="bi bi-file-earmark-pdf"></i></a></td></tr>
                            @endif
                            @if((int) $appointment->doctor_action > 0)
                                <tr><td>{{ __('medical_appointments.doctor_reply_pdf') }}</td><td><a target="_blank" href="{{ route($routes['document'], [$appointment->id, 'doctor-reply']) }}" class="btn btn-link"><i class="bi bi-file-earmark-pdf"></i></a></td></tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    @endpush
</div>

@push('modals')
<div class="modal fade" id="medicalCreate" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form method="POST" action="{{ route($routes['store']) }}" class="modal-content" id="medicalCreateForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('medical_appointments.create') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('medical_appointments.language') }}</label>
                        <select class="form-select" name="language" id="medicalLanguage" required>
                            <option value="1">عربي</option>
                            <option value="2">English</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('medical_appointments.language_doctor') }}</label>
                        <select class="form-select" name="language_doctor" id="medicalDoctorLanguage" required>
                            <option value="1">عربي</option>
                            <option value="2">English</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('medical_appointments.mobile') }}</label>
                        <input type="text" class="form-control" name="mobile" maxlength="20" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('medical_appointments.file_number') }}</label>
                        <input type="text" class="form-control" name="file_number" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('medical_appointments.department') }}</label>
                        <select class="form-select" name="department" id="medicalDepartment" required>
                            <option value="">{{ __('medical_appointments.choose') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->localizedName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('medical_appointments.doctor') }}</label>
                        <select class="form-select" name="physician" id="medicalPhysician" required>
                            <option value="">{{ __('medical_appointments.choose') }}</option>
                            @foreach($physicians as $physician)
                                <option value="{{ $physician->id }}">{{ $physician->localizedDisplayName() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('medical_appointments.procedure_place') }}</label>
                        <select class="form-select" name="procedure_place" required>
                            <option value="">{{ __('medical_appointments.choose') }}</option>
                            @foreach($procedurePlaces as $place)
                                <option value="{{ $place->id }}">{{ $place->localizedName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('medical_appointments.coverage_status') }}</label>
                        <select class="form-select" name="medical_coverage_status" required>
                            <option value="">{{ __('medical_appointments.choose') }}</option>
                            @foreach($coverageStatuses as $coverage)
                                <option value="{{ $coverage->id }}">{{ $coverage->localizedName() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div id="patientArabicFields" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('medical_appointments.patient_name') }}</label>
                                <input type="text" class="form-control" name="patient_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('medical_appointments.procedure_type') }}</label>
                                <input type="text" class="form-control" name="procedure_type">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('medical_appointments.procedure_duration') }}</label>
                                <input type="text" class="form-control" name="procedure_duration">
                            </div>
                        </div>
                        <div id="patientEnglishFields" class="row g-3 d-none">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('medical_appointments.patient_name_en') }}</label>
                                <input type="text" class="form-control" name="patient_name_en">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('medical_appointments.procedure_type') }} (EN)</label>
                                <input type="text" class="form-control" name="procedure_type_en">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('medical_appointments.procedure_duration') }} (EN)</label>
                                <input type="text" class="form-control" name="procedure_duration_en">
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">{{ __('medical_appointments.dates') }}</label>
                        <div id="medicalDateRows" class="vstack gap-2">
                            <div class="input-group medical-date-row">
                                <input type="datetime-local" class="form-control" name="date[]">
                                <button type="button" class="btn btn-outline-danger medical-remove-date">×</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary mt-2" id="medicalAddDate"><i class="bi bi-plus-lg"></i> {{ __('medical_appointments.add_date') }}</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">{{ __('medical_appointments.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endpush
@endsection

@push('scripts')
<script>
(function () {
    const language = document.getElementById('medicalLanguage');
    const patientArabicFields = document.getElementById('patientArabicFields');
    const patientEnglishFields = document.getElementById('patientEnglishFields');
    const department = document.getElementById('medicalDepartment');
    const physician = document.getElementById('medicalPhysician');
    const physicianUrl = @json(route($routes['physicians']));
    const dateRows = document.getElementById('medicalDateRows');

    function toggleLanguagePanels() {
        const arVisible = language.value === '1';
        patientArabicFields.classList.toggle('d-none', ! arVisible);
        patientEnglishFields.classList.toggle('d-none', arVisible);
    }

    function reloadPhysicians() {
        const url = new URL(physicianUrl, window.location.origin);
        if (department.value) {
            url.searchParams.set('department', department.value);
        }
        fetch(url.toString(), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then((response) => response.text())
            .then((html) => { physician.innerHTML = html; })
            .catch(() => {});
    }

    function addDateRow() {
        const row = document.createElement('div');
        row.className = 'input-group medical-date-row';
        row.innerHTML = '<input type="datetime-local" class="form-control" name="date[]"><button type="button" class="btn btn-outline-danger medical-remove-date">×</button>';
        dateRows.appendChild(row);
    }

    language.addEventListener('change', toggleLanguagePanels);
    department.addEventListener('change', reloadPhysicians);
    document.getElementById('medicalAddDate').addEventListener('click', addDateRow);
    dateRows.addEventListener('click', function (event) {
        if (event.target.classList.contains('medical-remove-date') && document.querySelectorAll('.medical-date-row').length > 1) {
            event.target.closest('.medical-date-row').remove();
        }
    });

    document.querySelectorAll('.medical-status').forEach(function (select) {
        select.addEventListener('change', function () {
            const id = this.dataset.target;
            document.getElementById('statusDate' + id).hidden = this.value !== '5';
            document.getElementById('statusReason' + id).hidden = this.value !== '12';
        });
    });

    toggleLanguagePanels();
})();
</script>
@endpush
