@extends('layouts.app')
@section('title', __('training.'.$mode))
@section('sidebar_heading', __('training.'.$mode))
@section('sidebar_subheading', __('training.subtitle'))
@section('content')
<div class="hm-module-page" data-module="training-management">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h3 mb-1">{{ __('training.'.$mode) }}</h1><p class="text-muted mb-0">{{ __('training.subtitle') }}</p></div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTraining"><i class="bi bi-plus-lg"></i> {{ __('training.new') }}</button>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card mb-4"><div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label">{{ __('training.from') }}</label><input type="date" class="form-control" name="from" value="{{ $filters['from'] ?? '' }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('training.to') }}</label><input type="date" class="form-control" name="to" value="{{ $filters['to'] ?? '' }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('training.status') }}</label><select class="form-select" name="status"><option value="">{{ __('training.all') }}</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected((string)($filters['status'] ?? '') === (string)$status->id)>{{ $status->name_ar }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('training.employee_search') }}</label><input class="form-control" name="employee" value="{{ $filters['employee'] ?? '' }}"></div>
            <div class="col-md-2 d-flex flex-wrap gap-2"><button class="btn btn-dark flex-grow-1">{{ __('training.search') }}</button><a href="{{ route($routes['index']) }}" class="btn btn-outline-secondary">{{ __('training.reset') }}</a></div>
        </form>
    </div></div>
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>{{ __('training.date') }}</th><th>{{ __('training.employee') }}</th><th>{{ __('training.job_title') }}</th><th>{{ __('training.employee_number') }}</th><th>{{ __('training.status') }}</th><th>{{ __('training.actions') }}</th></tr></thead>
        <tbody>@forelse($trainings as $training)<tr>
            <td>{{ $training->created_at }}</td><td>{{ $training->employee?->displayName() ?? '—' }}</td><td>{{ $training->employee?->jobTitle?->localizedName() ?? '—' }}</td><td>{{ $training->employee?->hr_username ?? '—' }}</td>
            <td><span class="badge bg-primary-subtle text-primary">{{ $training->currentStatus?->name_ar ?? $training->status }}</span></td>
            <td><a class="btn btn-sm btn-outline-primary" href="{{ route($routes['show'], $training->id) }}">{{ __('training.details') }}</a></td>
        </tr>@empty<tr><td colspan="6" class="text-center py-5 text-muted">{{ __('training.empty') }}</td></tr>@endforelse</tbody>
    </table></div>@if($trainings->hasPages())<div class="card-footer">{{ $trainings->links() }}</div>@endif</div>
</div>
<div class="modal fade" id="newTraining" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route($routes['store']) }}" class="modal-content">@csrf
    <div class="modal-header"><h2 class="modal-title fs-5">{{ __('training.new') }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">{{ __('training.employee') }}</label><select required name="employee_id" class="form-select"><option value="">—</option>@foreach($employees as $employee)<option value="{{ $employee->hr_id }}">{{ $employee->displayName() }} — {{ $employee->hr_username }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">{{ __('training.coordinator') }}</label><select required name="training_coordinator" class="form-select"><option value="">—</option>@foreach($coordinators as $coordinator)<option value="{{ $coordinator->hr_id }}">{{ $coordinator->displayName() }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">{{ __('training.begin_date') }}</label><input required type="date" name="begin_date" class="form-control" value="{{ old('begin_date', now()->format('Y-m-d')) }}"></div>
        <div class="col-md-4"><label class="form-label">{{ __('training.days') }}</label><input readonly class="form-control" value="6"></div><div class="col-md-4"><label class="form-label">{{ __('training.hours') }}</label><input readonly class="form-control" value="8"></div>
        <div class="col-md-6"><label class="form-label">{{ __('training.time_from') }}</label><input required type="time" name="time_from" class="form-control"></div><div class="col-md-6"><label class="form-label">{{ __('training.time_to') }}</label><input required type="time" name="time_to" class="form-control"></div>
    </div>@if($errors->any())<div class="alert alert-danger mt-3"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif</div>
    <div class="modal-footer"><button class="btn btn-primary">{{ __('training.save') }}</button></div>
</form></div></div>
@endsection
