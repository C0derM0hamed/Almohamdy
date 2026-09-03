@extends('layouts.app')
@php($employeeMode = $employeeMode ?? false)
@php($reportRoutePrefix = $employeeMode ? 'modules.admission-inpatient.employee-report9' : 'modules.admission-inpatient.report9')
@section('title', $employeeMode ? 'تفاصيل تقرير الموظفين' : 'تفاصيل تقرير التنويم')
@section('content')
<div class="container-fluid py-3" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
        <div><h1 class="h4">{{ $employeeMode ? 'تقرير الموظفين' : 'تقرير التنويم' }} #{{ $row->id }}</h1><p class="text-muted">{{ $row->period_record->name_ar ?? $row->period_record->name_en ?? $row->period }} — {{ is_numeric($row->date) ? date('Y-m-d H:i',(int)$row->date) : $row->date }}</p></div>
        <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="{{ route($reportRoutePrefix.'.pdf',$row->id) }}">PDF</a><a class="btn btn-outline-primary" href="{{ route($reportRoutePrefix.'.edit',$row->id) }}">تعديل</a><form method="post" action="{{ route($reportRoutePrefix.'.destroy',$row->id) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger">حذف</button></form></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><small>الموقع</small><div>{{ $row->place_record->name_ar ?? $row->place_record->name_en ?? $row->rep_place ?? '—' }}</div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><small>المنشئ</small><div>{{ trim(($row->creator_record->hr_first_name ?? '').' '.($row->creator_record->hr_last_name ?? '')) ?: $row->creator }}</div></div></div></div>
        @unless($employeeMode)<div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><small>الحضور والغياب</small><div>{{ $row->attendance ? 'حضور '.$row->attendance->attendees.' — غياب '.$row->attendance->absence.' — استئذان '.$row->attendance->permissible.' — تأخير '.$row->attendance->latecomers : 'لا توجد بيانات حضور' }}</div></div></div></div>@endunless
    </div>

    <div class="card border-0 shadow-sm mb-3"><div class="card-body"><h2 class="h6">المرضى والملاحظات</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>التاريخ</th><th>الملف</th><th>الموقع</th><th>الغرفة/السرير</th><th>القسم</th><th>الملاحظة</th><th>الإجراء</th><th>أخرى</th><th>مرفق</th></tr></thead><tbody>
    @forelse($row->entries as $entry)
        <tr><td>{{ is_numeric($entry->date ?? null) ? date('Y-m-d',(int)$entry->date) : ($entry->date ?? '') }}</td><td>{{ $entry->filenumber }}</td><td>{{ $entry->location_record->name_ar ?? $entry->location_record->name_en ?? $entry->location }}</td><td>{{ $entry->room_bod_number }}</td><td>{{ $entry->section_record->name_ar ?? $entry->section_record->name_en ?? $entry->section }}</td><td>{{ $entry->notice_record->name_ar ?? $entry->notice_record->name_en ?? $entry->notice }}</td><td>{{ $entry->action_record->name_ar ?? $entry->action_record->name_en ?? $entry->action }}</td><td>{{ $entry->other }}</td><td>@if(!empty($entry->files))<a class="badge bg-success text-decoration-none" href="{{ route($reportRoutePrefix.'.file', [$row->id, 'entries', $entry->id]) }}">تحميل المرفق</a>@else—@endif</td></tr>
    @empty
        <tr><td colspan="9">لا توجد صفوف.</td></tr>
    @endforelse
    </tbody></table></div></div></div>

    <div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h6">الخدمات المساندة</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>التاريخ</th><th>الإدارة</th><th>نوع الخدمة</th><th>نوع الطلب</th><th>الوصف</th><th>مرفق</th></tr></thead><tbody>
    @forelse($row->support_services as $entry)
        <tr><td>{{ is_numeric($entry->date ?? null) ? date('Y-m-d',(int)$entry->date) : ($entry->date ?? '') }}</td><td>{{ $entry->maintenance_department_record->name_ar ?? $entry->maintenance_department_record->name_en ?? $entry->maintenance_departments }}</td><td>{{ $entry->maintenance_type_record->name_ar ?? $entry->maintenance_type_record->name_en ?? $entry->maintenance_type }}</td><td>{{ $entry->request_type_record->name_ar ?? $entry->request_type_record->name_en ?? $entry->maintenance_request_type }}</td><td>{{ $entry->description }}</td><td>@if(!empty($entry->files))<a class="badge bg-success text-decoration-none" href="{{ route($reportRoutePrefix.'.file', [$row->id, 'support', $entry->id]) }}">تحميل المرفق</a>@else—@endif</td></tr>
    @empty
        <tr><td colspan="6">لا توجد خدمات مساندة.</td></tr>
    @endforelse
    </tbody></table></div></div></div>
</div>
@endsection
