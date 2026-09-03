@extends('layouts.app')
@section('title', 'دليل الباقات الطبية')
@section('content')
<div class="container-fluid py-3" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h1 class="h4 mb-1">دليل الباقات الطبية</h1><p class="text-muted mb-0">عرض الباقات المنشورة حسب العيادة وشركة التأمين وقسم الأكواد.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('modules.admission-inpatient.packages.index') }}">إدارة حزم التنويم</a>
    </div>
    <form class="card border-0 shadow-sm mb-3"><div class="card-body row g-2">
        <div class="col-md-4"><label class="form-label">العيادة</label><select class="form-select" name="cid"><option value="">كل العيادات</option>@foreach($clinics as $item)<option value="{{ $item->id }}" @selected((int)($filters['specialized_clinics_id'] ?? 0) === (int)$item->id)>{{ $item->name_ar ?? $item->subject_ar ?? $item->name_en ?? $item->subject_en ?? $item->id }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">شركة التأمين</label><select class="form-select" name="inid"><option value="">كل الشركات</option>@foreach($insuranceCompanies as $item)<option value="{{ $item->id }}" @selected((int)($filters['insurance_companies_id'] ?? 0) === (int)$item->id)>{{ $item->name_ar ?? $item->name_en ?? $item->id }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">قسم الأكواد</label><select class="form-select" name="id"><option value="">كل الأقسام</option>@foreach($sections as $item)<option value="{{ $item->id }}" @selected((int)($filters['codes_sections_id'] ?? 0) === (int)$item->id)>{{ $item->name_ar ?? $item->name_en ?? $item->id }}</option>@endforeach</select></div>
        <div class="col-12"><button class="btn btn-primary">عرض الباقات</button></div>
    </div></form>
    @foreach($rows as $kind => $items)
        <section class="card border-0 shadow-sm mb-3"><div class="card-body"><h2 class="h6">{{ ['pharmacy'=>'الصيدلية','rays'=>'الأشعة','laboratory'=>'المختبر','natural_therapy'=>'العلاج الطبيعي','surgeries'=>'العمليات'][$kind] ?? $kind }}</h2>
            <div class="row g-3">@forelse($items as $item)<div class="col-md-4"><article class="border rounded p-3 h-100"><h3 class="h6">{{ $item->name_ar ?? $item->disease_name_ar ?? $item->name_en ?? $item->disease_name_en ?? '—' }}</h3><p class="mb-1"><strong>الكود:</strong> {{ $item->code ?? $item->diagnostic_code ?? '—' }}</p><p class="mb-1">{{ $item->notice_ar ?? $item->notice_en ?? '' }}</p>@if(isset($item->medicament_name_ar))<p class="mb-0"><strong>الدواء:</strong> {{ strip_tags((string)$item->medicament_name_ar) }}</p>@endif</article></div>@empty<div class="col-12 text-muted">لا توجد باقات منشورة لهذا الاختيار.</div>@endforelse</div>
        </div></section>
    @endforeach
</div>
@endsection
