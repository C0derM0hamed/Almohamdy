@extends('layouts.app')
@section('title','تقرير التنويم رقم 9')
@section('content')
<div class="container-fluid py-3" dir="rtl">
    <div class="d-flex justify-content-between mb-3">
        <div><h1 class="h4">تقرير التنويم رقم 9</h1><p class="text-muted">عرض تقارير قسم التنويم حسب الموظف والفترة والمدى الزمني.</p></div>
        <a class="btn btn-primary" href="{{ route('modules.admission-inpatient.report9.create') }}">تقرير جديد</a>
    </div>

    <form class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2">
            <div class="col-md-3"><label class="form-label">الموظف</label><select class="form-select" name="creator"><option value="">الكل</option>@foreach($employees as $employee)<option value="{{ $employee->hr_id }}" @selected((int)$filters['creator'] === (int)$employee->hr_id)>{{ $employee->hr_username }} — {{ $employee->hr_first_name }} {{ $employee->hr_last_name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">الفترة</label><select class="form-select" name="period_id"><option value="">الكل</option>@foreach($periods as $period)<option value="{{ $period->id }}" @selected((int)$filters['period_id'] === (int)$period->id)>{{ $period->name_ar ?: $period->name_en }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">من</label><input type="date" class="form-control" name="from" value="{{ $filters['from'] }}"></div>
            <div class="col-md-2"><label class="form-label">إلى</label><input type="date" class="form-control" name="to" value="{{ $filters['to'] }}"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100">بحث</button></div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        @foreach([['الحضور','attendees','primary'],['الغياب','absence','danger'],['الاستئذان','permissible','warning'],['المتأخرون','latecomers','info']] as [$label,$key,$color])
            <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">{{ $label }}</small><div class="fs-4 text-{{ $color }}">{{ (int)($attendanceTotals[$key] ?? 0) }}</div></div></div></div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>#</th><th>الفترة</th><th>التاريخ</th><th>الموقع</th><th>المنشئ</th><th></th></tr></thead><tbody>
    @forelse($reports as $row)
        <tr><td>{{ $row->id }}</td><td>{{ $row->period_record->name_ar ?? $row->period_record->name_en ?? $row->period }}</td><td dir="ltr">{{ is_numeric($row->date) ? date('Y-m-d H:i',(int)$row->date) : $row->date }}</td><td>{{ $row->place_record->name_ar ?? $row->place_record->name_en ?? $row->rep_place ?? '—' }}</td><td>{{ trim(($row->creator_record->hr_first_name ?? '').' '.($row->creator_record->hr_last_name ?? '')) ?: $row->creator }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('modules.admission-inpatient.report9.show',$row->id) }}">عرض</a></td></tr>
    @empty
        <tr><td colspan="6" class="text-center py-5 text-muted">لا توجد تقارير.</td></tr>
    @endforelse
    </tbody></table></div><div class="p-3">{{ $reports->links() }}</div></div>
</div>
@endsection
