@extends('layouts.app')
@section('title', 'تقارير الموظفين')
@section('content')
<div class="container-fluid py-3" dir="rtl">
    <div class="d-flex justify-content-between mb-3">
        <div><h1 class="h4">تقارير الموظفين</h1><p class="text-muted">عرض تقارير موظفي قسم التنويم بنفس نطاق الفرع والفترة في النظام القديم.</p></div>
        <a class="btn btn-primary" href="{{ route('modules.admission-inpatient.employee-report9.create') }}">تقرير جديد</a>
    </div>
    <form class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2">
            <div class="col-md-3"><label class="form-label">الموظف</label><select class="form-select" name="creator"><option value="">الكل</option>@foreach($employees as $employee)<option value="{{ $employee->hr_id }}" @selected((int) $filters['creator'] === (int) $employee->hr_id)>{{ $employee->hr_username }} — {{ $employee->hr_first_name }} {{ $employee->hr_last_name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ app()->getLocale() === 'ar' ? 'الفترة' : 'Period' }}</label><select class="form-select" name="period_id"><option value="">{{ app()->getLocale() === 'ar' ? 'الكل' : 'All' }}</option>@foreach($periods as $period)<option value="{{ $period->id }}" @selected((int) $filters['period_id'] === (int) $period->id)>{{ \App\Support\LocaleText::localizedValue($period->name_ar ?? null, $period->name_en ?? null) }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">من</label><input type="date" class="form-control" name="from" value="{{ $filters['from'] }}"></div>
            <div class="col-md-2"><label class="form-label">إلى</label><input type="date" class="form-control" name="to" value="{{ $filters['to'] }}"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100">بحث</button></div>
        </div>
    </form>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>#</th><th>الفترة</th><th>التاريخ</th><th>الموقع</th><th>المنشئ</th><th></th></tr></thead><tbody>
    @forelse($reports as $row)
        <tr><td>{{ $row->id }}</td><td>{{ \App\Support\LocaleText::localizedValue($row->period_record->name_ar ?? null, $row->period_record->name_en ?? null) ?: $row->period }}</td><td dir="ltr">{{ is_numeric($row->date) ? date('Y-m-d H:i', (int) $row->date) : $row->date }}</td><td>{{ \App\Support\LocaleText::localizedValue($row->place_record->name_ar ?? null, $row->place_record->name_en ?? null) ?: ($row->rep_place ?? '—') }}</td><td>{{ trim(($row->creator_record->hr_first_name ?? '').' '.($row->creator_record->hr_last_name ?? '')) ?: $row->creator }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('modules.admission-inpatient.employee-report9.show', $row->id) }}">{{ app()->getLocale() === 'ar' ? 'عرض' : 'View' }}</a></td></tr>
    @empty
        <tr><td colspan="6" class="text-center py-5 text-muted">لا توجد تقارير.</td></tr>
    @endforelse
    </tbody></table></div><div class="p-3">{{ $reports->links() }}</div></div>
</div>
@endsection
