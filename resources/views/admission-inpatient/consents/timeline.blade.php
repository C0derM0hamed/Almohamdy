@extends('layouts.app')
@section('title','الخط الزمني لإقرار التنويم')
@section('content')
<div class="container-fluid py-3" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h1 class="h4 mb-1">الخط الزمني لإقرار التنويم #{{ $row->id }}</h1><p class="text-muted mb-0">{{ $row->patient_name_ar ?: $row->patient_name_en }} — {{ $row->reference_number }}</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('modules.admission-inpatient.consents.show',$row->id) }}">رجوع للإقرار</a>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><small>هوية المريض</small><div>{{ $row->patient_idno }}</div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><small>المتعهد</small><div>{{ $row->contractor_name_ar ?: $row->contractor_name_en }}</div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><small>الجوال</small><div dir="ltr">{{ $row->contractor_mobile }}</div></div></div></div>
    </div>
    <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="position-relative">
            @foreach($items as $item)
                <div class="d-flex gap-3 pb-4 position-relative">
                    <div class="rounded-circle flex-shrink-0 mt-1 {{ $item['status'] === 'completed' ? 'bg-success' : ($item['status'] === 'rejected' ? 'bg-danger' : 'bg-secondary') }}" style="width:14px;height:14px"></div>
                    <div class="border rounded p-3 flex-grow-1"><div class="fw-bold">{{ $item['title'] }}</div><small class="text-muted">{{ $item['date'] ?: 'بانتظار الإجراء' }}</small><div class="mt-2" style="white-space:pre-line">{{ $item['body'] }}</div></div>
                </div>
            @endforeach
        </div>
    </div></div>
</div>
@endsection
