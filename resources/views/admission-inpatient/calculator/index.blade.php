@extends('layouts.app')
@php($pageTitle = $type === 'manual' ? 'الحاسبة اليدوية للتنويم' : 'حاسبة التنويم')
@section('title', $pageTitle)
@section('figma_page_header', 'true')
@push('workflow_styles')<link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">@endpush
@section('content')
<div class="hm-fm hm-workflow">
    @include('layouts.partials.figma-module-header', ['crumbs' => [['label' => __('dashboard.modules')], ['label' => 'التنويم'], ['label' => $pageTitle]], 'title' => $pageTitle, 'subtitle' => 'سجلات الشركة الحالية كما في الحاسبة القديمة', 'heroIcon' => 'bi-calculator', 'actionUrl' => route('modules.admission-inpatient.calculator.create', [$type, 'direct']), 'actionLabel' => 'تسعيرة جديدة'])
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-primary" href="{{ route('modules.admission-inpatient.calculator.create', [$type, 'procedures']) }}">إجراء جديد</a>
        @if ($type === 'standard')<a class="btn btn-outline-primary" href="{{ route('modules.admission-inpatient.calculator.create', [$type, 'observation']) }}">تحت الملاحظة جديد</a>@endif
    </div>
    <form class="wf-search-panel" method="get"><h2>بحث</h2><div class="wf-filter-grid wf-filter-grid--three">
        <div class="wf-field"><label>رقم الملف</label><input name="file_number" value="{{ $filters['file_number'] }}"></div>
        <div class="wf-field"><label>من تاريخ</label><input type="date" name="from" value="{{ $filters['from'] }}"></div>
        <div class="wf-field"><label>إلى تاريخ</label><input type="date" name="to" value="{{ $filters['to'] }}"></div>
        <div class="wf-field"><label>المستخدم</label><select name="user_id"><option value="">الكل</option>@foreach($options['users'] ?? [] as $user)<option value="{{ $user->hr_id }}" @selected((int)$filters['user_id'] === (int)$user->hr_id)>{{ trim(($user->hr_first_name ?? '').' '.($user->hr_last_name ?? '')) }}</option>@endforeach</select></div>
        <div class="wf-field"><label>نوع الغرفة</label><select name="room_type"><option value="">الكل</option>@foreach($options['roomTypes'] ?? [] as $roomType)<option value="{{ $roomType->id }}" @selected((int)$filters['room_type'] === (int)$roomType->id)>{{ $roomType->name_ar ?: $roomType->name_en }}</option>@endforeach</select></div>
        <button class="wf-search-btn" type="submit"><i class="bi bi-search"></i></button>
    </div></form>
    <section class="wf-table-panel wf-table-panel--contained">
        @include('layouts.partials.figma-workflow-table-head', ['title' => $pageTitle, 'items' => $records])
        <div class="table-responsive"><table class="wf-table"><thead><tr><th>التاريخ</th><th>النوع</th><th>المريض</th><th>الملف</th><th>الأيام</th><th>الغرفة</th><th></th></tr></thead><tbody>
        @forelse($records as $record)
            <tr><td dir="ltr">{{ date('Y-m-d H:i', (int) $record->date) }}</td><td>{{ (int) ($record->type ?? 0) === 1 ? 'إجراءات' : ((int) ($record->type ?? 0) === 2 ? 'تحت الملاحظة' : 'تسعيرة') }}</td><td>{{ $record->patient_name }}</td><td><span class="wf-code">{{ $record->file_number }}</span></td><td>{{ $record->days }}</td><td>{{ $record->room }}</td><td><a class="wf-view" href="{{ route('modules.admission-inpatient.calculator.show', [$type, $record->id]) }}"><i class="bi bi-eye"></i></a></td></tr>
        @empty<tr><td colspan="7" class="text-center text-muted py-5">لا توجد تسعيرات.</td></tr>@endforelse
        </tbody></table></div><div class="p-3">{{ $records->links() }}</div>
    </section>
</div>
@endsection
