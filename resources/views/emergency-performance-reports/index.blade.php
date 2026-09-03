@extends('layouts.app')
@section('title', __('emergency_reports.title'))
@section('content')
<div class="container-fluid py-3" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h1 class="h4 mb-1">{{ __('emergency_reports.title') }}</h1><p class="text-muted mb-0">{{ __('emergency_reports.subtitle') }}</p></div>
        @if ($filters['submitted'])
            <a class="btn btn-outline-secondary" href="{{ route('modules.emergency-reports.pdf', request()->query()) }}" title="{{ __('emergency_reports.pdf') }}"><i class="bi bi-file-earmark-pdf me-1"></i>{{ __('emergency_reports.pdf') }}</a>
        @endif
    </div>
    <form method="get" class="card border-0 shadow-sm mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label" for="from">{{ __('emergency_reports.from') }}</label><input class="form-control" type="date" id="from" name="from" value="{{ $filters['from'] }}" required></div>
            <div class="col-md-3"><label class="form-label" for="to">{{ __('emergency_reports.to') }}</label><input class="form-control" type="date" id="to" name="to" value="{{ $filters['to'] }}" required></div>
            <div class="col-md-3"><label class="form-label" for="employee">{{ __('emergency_reports.employee') }}</label><select class="form-select" id="employee" name="employee"><option value="all">{{ __('emergency_reports.all') }}</option>@foreach ($options['employees'] as $employee)<option value="{{ $employee->hr_id }}" @selected($filters['employee'] === (string) $employee->hr_id)>{{ $employee->hr_username }} - {{ $employee->hr_first_name }} {{ $employee->hr_last_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label" for="period">{{ __('emergency_reports.period') }}</label><select class="form-select" id="period" name="period"><option value="all">{{ __('emergency_reports.all') }}</option>@foreach ($options['periods'] as $period)<option value="{{ $period->id }}" @selected($filters['period'] === (string) $period->id)>{{ \App\Support\LocaleText::localizedValue($period->name_ar ?? null, $period->name_en ?? null) }}</option>@endforeach</select></div>
            <div class="col-md-1"><button class="btn btn-primary w-100" name="show" value="1" type="submit" title="{{ __('emergency_reports.show') }}"><i class="bi bi-search"></i></button></div>
        </div>
    </form>
    @if ($filters['submitted'])
        <div class="row g-3 mb-4">@foreach ([['attendees','bi-person-check'],['absence','bi-person-x'],['permissible','bi-clock-history'],['latecomers','bi-alarm']] as [$key,$icon])<div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center gap-3"><i class="bi {{ $icon }} fs-3 text-primary"></i><div><div class="text-muted small">{{ __('emergency_reports.'.$key) }}</div><strong class="fs-4">{{ $attendance[$key] }}</strong></div></div></div></div>@endforeach</div>
        <div class="row g-3 mb-4">@foreach ([['inpatient','إجمالي التنويم النقدي'],['inpatient_insurance','إجمالي التنويم بالتأمين'],['red_crescent','إجمالي حالات الهلال الأحمر'],['efada_approval','إجمالي الحالات المعتمدة'],['efada_non_approval','إجمالي الحالات غير المعتمدة'],['emergency','إجمالي الحالات الطارئة']] as [$key,$label])<div class="col-6 col-xl-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div><strong class="fs-4">{{ $reports->sum($key) }}</strong></div></div></div>@endforeach</div>
        @forelse ($sections as $section)
            <section class="card border-0 shadow-sm mb-4"><div class="card-header bg-transparent"><h2 class="h6 mb-0">{{ $section['title'] }} <span class="badge text-bg-light">{{ $section['rows']->count() }}</span></h2></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>#</th>@foreach ($section['columns'] as $column)<th>{{ $column['label'] }}</th>@endforeach<th>{{ __('emergency_reports.date') }}</th><th>{{ __('emergency_reports.period_label') }}</th><th>{{ __('emergency_reports.creator') }}</th>@if ($section['file'])<th>{{ __('emergency_reports.attachment') }}</th>@endif</tr></thead><tbody>@foreach ($section['rows'] as $index => $row)<tr><td>{{ $index + 1 }}</td>@foreach ($section['columns'] as $column)<td>{{ is_scalar($row->{$column['key']} ?? null) ? $row->{$column['key']} : '' }}</td>@endforeach<td dir="ltr">{{ $row->report_date }}</td><td>{{ $row->period_label }}</td><td>{{ $row->creator_label }}</td>@if ($section['file'])<td>@if (filled($row->{$section['file']} ?? null))<a class="btn btn-sm btn-outline-secondary" href="{{ route('modules.emergency-reports.attachment', [$row->attachment_section, $row->id]) }}" title="{{ __('emergency_reports.attachment') }}"><i class="bi bi-paperclip"></i></a>@endif</td>@endif</tr>@endforeach</tbody></table></div></section>
        @empty
            <div class="alert alert-light border">{{ __('emergency_reports.no_records') }}</div>
        @endforelse
    @endif
</div>
@endsection
