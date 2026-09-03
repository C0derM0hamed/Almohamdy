<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:10px;direction:{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};text-align:right}h1{font-size:18px;margin:0 0 5px}h2{font-size:13px;margin:18px 0 5px}p{margin:0 0 10px;color:#555}.summary{width:100%;border-collapse:collapse;margin:8px 0 14px}.summary td{border:1px solid #aaa;padding:5px}.report{width:100%;border-collapse:collapse;margin:0 0 14px}.report th,.report td{border:1px solid #999;padding:4px;vertical-align:top}.report th{background:#eee;font-weight:bold}.muted{color:#666}</style></head>
<body>
<h1>{{ __('department_reports.departments.'.$department.'.title') }}</h1>
<p>{{ __('department_reports.period_summary', ['from' => $filters['from'], 'to' => $filters['to']]) }}</p>
<table class="summary"><tr><td>{{ __('department_reports.report_count') }}</td><td>{{ $reports->count() }}</td><td>{{ __('department_reports.detail_count') }}</td><td>{{ $totalRows }}</td>@foreach ($summary as $item)<td>{{ __('department_reports.summary.'.$item['label']) }}</td><td>{{ $item['total'] }}</td>@endforeach</tr></table>
@foreach ($sections as $section)
    <h2>{{ __('department_reports.sections.'.$section['title']) }} ({{ $section['rows']->count() }})</h2>
    <table class="report"><thead><tr><th>#</th>@foreach ($section['columns'] as $column)<th>{{ __('department_reports.columns.'.$column['label']) }}</th>@endforeach<th>{{ __('department_reports.date') }}</th><th>{{ __('department_reports.period_label') }}</th><th>{{ __('department_reports.creator') }}</th></tr></thead><tbody>
    @foreach ($section['rows'] as $index => $row)<tr><td>{{ $index + 1 }}</td>@foreach ($section['columns'] as $column)@php($value = $row->{$column['key']} ?? '')<td>{{ is_scalar($value) ? $value : '' }}</td>@endforeach<td>{{ $row->report_date }}</td><td>{{ $row->period_label }}</td><td>{{ $row->creator_label }}</td></tr>@endforeach
    </tbody></table>
@endforeach
</body></html>
