@extends('layouts.app')

@section('title', __('department_reports.departments.'.$department.'.title'))
@section('figma_page_header', 'true')

@push('styles')
    <link href="{{ asset('css/hm-detail-stat-cards.css') }}?v={{ filemtime(public_path('css/hm-detail-stat-cards.css')) }}" rel="stylesheet">
@endpush

@section('content')
<div class="hm-workflow" dir="rtl">
    @include('layouts.partials.figma-module-header', [
        'crumbs' => [['label' => __('dashboard.modules')], ['label' => __('department_reports.title')], ['label' => __('department_reports.departments.'.$department.'.title')]],
        'title' => __('department_reports.departments.'.$department.'.title'),
        'subtitle' => __('department_reports.departments.'.$department.'.subtitle'),
        'heroIcon' => 'bi-bar-chart-line',
    ])

    <form method="get" class="card border-0 shadow-sm mb-4">
        <div class="card-body row g-3 align-items-end" data-filter-title="{{ __('department_reports.filters') }}">
            <div class="col-md-3">
                <label class="form-label" for="department-report-from">{{ __('department_reports.from') }}</label>
                <input class="form-control" type="date" id="department-report-from" name="from" value="{{ $filters['from'] }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="department-report-to">{{ __('department_reports.to') }}</label>
                <input class="form-control" type="date" id="department-report-to" name="to" value="{{ $filters['to'] }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="department-report-employee">{{ __('department_reports.employee') }}</label>
                <select class="form-select" id="department-report-employee" name="employee">
                    <option value="all">{{ __('department_reports.all') }}</option>
                    @foreach ($options['employees'] as $employee)
                        <option value="{{ $employee->hr_id }}" @selected($filters['employee'] === (string) $employee->hr_id)>{{ trim($employee->hr_username.' - '.$employee->hr_first_name.' '.$employee->hr_last_name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="department-report-period">{{ __('department_reports.period') }}</label>
                <select class="form-select" id="department-report-period" name="period">
                    <option value="all">{{ __('department_reports.all') }}</option>
                    @foreach ($options['periods'] as $period)
                        <option value="{{ $period->id }}" @selected($filters['period'] === (string) $period->id)>{{ \App\Support\LocaleText::localizedValue($period->name_ar ?? null, $period->name_en ?? null) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100" name="show" value="1" type="submit" title="{{ __('department_reports.show') }}"><i class="bi bi-search"></i></button>
            </div>
        </div>
    </form>

    @if ($filters['submitted'])
        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
            <div class="text-muted small">{{ __('department_reports.period_summary', ['from' => $filters['from'], 'to' => $filters['to']]) }}</div>
            <a class="btn btn-outline-secondary" href="{{ route('modules.department-reports.pdf', ['department' => $department] + request()->query()) }}" title="{{ __('department_reports.pdf') }}"><i class="bi bi-file-earmark-pdf me-1"></i>{{ __('department_reports.pdf') }}</a>
        </div>

        <div class="hm-detail-stats hm-detail-stats--auto">
            <article class="hm-detail-stat hm-detail-stat--primary">
                <span class="hm-detail-stat__icon" aria-hidden="true"><i class="bi bi-file-earmark-bar-graph"></i></span>
                <span class="hm-detail-stat__copy">
                    <span class="hm-detail-stat__label">{{ __('department_reports.report_count') }}</span>
                    <strong class="hm-detail-stat__value">{{ $reports->count() }}</strong>
                    <span class="hm-detail-stat__meta">{{ __('department_reports.departments.'.$department.'.title') }}</span>
                </span>
            </article>
            <article class="hm-detail-stat hm-detail-stat--dark">
                <span class="hm-detail-stat__icon" aria-hidden="true"><i class="bi bi-list-check"></i></span>
                <span class="hm-detail-stat__copy">
                    <span class="hm-detail-stat__label">{{ __('department_reports.detail_count') }}</span>
                    <strong class="hm-detail-stat__value">{{ $totalRows }}</strong>
                    <span class="hm-detail-stat__meta">{{ __('department_reports.period_summary', ['from' => $filters['from'], 'to' => $filters['to']]) }}</span>
                </span>
            </article>
            @foreach ($summary as $item)
                <article class="hm-detail-stat {{ $loop->even ? 'hm-detail-stat--dark' : 'hm-detail-stat--primary' }}">
                    <span class="hm-detail-stat__icon" aria-hidden="true"><i class="bi {{ $item['icon'] }}"></i></span>
                    <span class="hm-detail-stat__copy">
                        <span class="hm-detail-stat__label">{{ __('department_reports.summary.'.$item['label']) }}</span>
                        <strong class="hm-detail-stat__value">{{ rtrim(rtrim(number_format((float) $item['total'], 2, '.', ''), '0'), '.') }}</strong>
                        <span class="hm-detail-stat__meta">{{ __('department_reports.departments.'.$department.'.title') }}</span>
                    </span>
                </article>
            @endforeach
        </div>

        @forelse ($sections as $section)
            <section class="hm-workflow__panel card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center gap-2">
                    <h2 class="h6 mb-0">{{ __('department_reports.sections.'.$section['title']) }}</h2>
                    <span class="badge text-bg-light">{{ $section['rows']->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>#</th>@foreach ($section['columns'] as $column)<th>{{ __('department_reports.columns.'.$column['label']) }}</th>@endforeach<th>{{ __('department_reports.date') }}</th><th>{{ __('department_reports.period_label') }}</th><th>{{ __('department_reports.creator') }}</th>@if ($section['file'])<th>{{ __('department_reports.attachment') }}</th>@endif</tr></thead>
                        <tbody>
                        @foreach ($section['rows'] as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                @foreach ($section['columns'] as $column)
                                    @php($value = $row->{$column['key']} ?? '')
                                    <td>{{ is_scalar($value) ? $value : '' }}</td>
                                @endforeach
                                <td dir="ltr">{{ $row->report_date }}</td>
                                <td>{{ $row->period_label }}</td>
                                <td>{{ $row->creator_label }}</td>
                                @if ($section['file'])
                                    <td>@if (filled($row->{$section['file']} ?? null))<a class="btn btn-sm btn-outline-secondary" href="{{ route('modules.department-reports.attachment', [$department, $section['attachment_section'], $row->id]) }}" title="{{ __('department_reports.attachment') }}"><i class="bi bi-paperclip"></i></a>@endif</td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="alert alert-light border">{{ __('department_reports.no_records') }}</div>
        @endforelse
    @endif
</div>
@endsection
