@extends('layouts.app')
@section('title', 'إضافة مطالبة مالية')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h4 mb-1">إضافة مطالبة مالية</h1><p class="text-muted mb-0">تُعرض الحقول طبقًا لنوع السداد كما في النظام السابق.</p></div>
    <a class="btn btn-outline-secondary" href="{{ route('modules.legal-claims.index') }}"><i class="bi bi-arrow-right"></i> رجوع</a>
</div>
<form id="claim-form" class="card border-0 shadow-sm" method="post" action="{{ route('modules.legal-claims.store') }}" enctype="multipart/form-data">
@csrf
<div class="card-body row g-3">
    <div class="col-md-4"><label class="form-label">رقم الملف *</label><input id="file-number" class="form-control" name="file_number" value="{{ old('file_number') }}" required><small class="text-muted">تُسترجع بيانات ضمان الدفع تلقائيًا عند توفرها.</small></div>
    <div class="col-md-4"><label class="form-label">الخدمة المقدمة للمريض *</label><input class="form-control" name="service_provided_to_patient" value="{{ old('service_provided_to_patient') }}" required></div>
    <div class="col-md-4"><label class="form-label">نوع السداد *</label><select id="payment-type" class="form-select" name="lawsuit_payment_type_id" required><option value="">اختر النوع</option>@foreach($paymentTypes as $item)<option value="{{ $item->id }}" @selected(old('lawsuit_payment_type_id') == $item->id)>{{ $item->name_ar }}</option>@endforeach</select></div>

    <div class="col-12"><hr><h2 class="h6">بيانات المريض والتنويم</h2></div>
    <div class="col-md-4"><label class="form-label">اسم المريض *</label><input id="patient-name" class="form-control" name="patient_name" value="{{ old('patient_name') }}" required></div>
    <div class="col-md-4"><label class="form-label">رقم هوية المريض</label><input id="patient-idno" class="form-control" name="patient_idno" value="{{ old('patient_idno') }}"></div>
    <div class="col-md-4"><label class="form-label">جنسية المريض</label><select id="patient-nationality" class="form-select" name="patient_nationality"><option value="">اختر الجنسية</option>@foreach($nationalities as $item)<option value="{{ $item->CODE }}" @selected(old('patient_nationality') == $item->CODE)>{{ $item->DESCRIPTION }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">تاريخ التنويم</label><input class="form-control" type="date" name="admission_date" value="{{ old('admission_date') }}"></div>
    <div class="col-md-3"><label class="form-label">تاريخ الخروج</label><input class="form-control" type="date" name="discharge_date" value="{{ old('discharge_date') }}"></div>
    <div class="col-md-3"><label class="form-label">موقع التنويم</label><select class="form-select" name="lawsuit_admission_location_id"><option value="">اختر الموقع</option>@foreach($locations as $item)<option value="{{ $item->id }}" @selected(old('lawsuit_admission_location_id') == $item->id)>{{ $item->name_ar }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">الجنس</label><select id="sex-code" class="form-select" name="sexCode"><option value="">اختر</option><option value="1">ذكر</option><option value="2">أنثى</option></select></div>
    <div class="col-md-3"><label class="form-label">نوع التاريخ</label><input id="date-type" class="form-control" name="date_type" value="{{ old('date_type') }}"></div>
    <div class="col-md-3"><label class="form-label">يوم الميلاد</label><input id="birth-day" class="form-control" type="number" name="birth_day" value="{{ old('birth_day') }}"></div>
    <div class="col-md-3"><label class="form-label">شهر الميلاد</label><input id="birth-month" class="form-control" type="number" name="birth_month" value="{{ old('birth_month') }}"></div>
    <div class="col-md-3"><label class="form-label">سنة الميلاد</label><input id="birth-year" class="form-control" type="number" name="birth_year" value="{{ old('birth_year') }}"></div>

    <div class="col-12"><hr><h2 class="h6">بيانات المتعهد</h2></div>
    <div class="col-md-4"><label class="form-label">اسم المتعهد</label><input id="liable-name" class="form-control" name="liable_name" value="{{ old('liable_name') }}"></div>
    <div class="col-md-4"><label class="form-label">هوية المتعهد</label><input id="liable-idno" class="form-control" name="liable_idno" value="{{ old('liable_idno') }}"></div>
    <div class="col-md-4"><label class="form-label">جنسية المتعهد</label><select id="liable-nationality" class="form-select" name="liable_nationality"><option value="">اختر الجنسية</option>@foreach($nationalities as $item)<option value="{{ $item->CODE }}">{{ $item->DESCRIPTION }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">جوال المتعهد</label><input id="liable-mobile" class="form-control" name="liable_mobile" value="{{ old('liable_mobile') }}"></div>
    <div class="col-md-6"><label class="form-label">تاريخ موافقة المتعهد</label><input id="contractor-approval-date" class="form-control" type="date" name="contractor_approval_date" value="{{ old('contractor_approval_date') }}"></div>

    <div class="col-12 d-none" data-payment-section="1"><hr><h2 class="h6">مبالغ السداد النقدي</h2></div>
    <div class="col-md-6 d-none" data-payment-section="1"><label class="form-label">المبلغ المدفوع *</label><input class="form-control" type="number" step="0.01" name="amount_paid" value="{{ old('amount_paid') }}"></div>
    <div class="col-md-6 d-none" data-payment-section="1"><label class="form-label">المبلغ المتبقي *</label><input class="form-control" type="number" step="0.01" name="amount_rest" value="{{ old('amount_rest') }}"></div>

    <div class="col-12 d-none" data-payment-section="2,3"><hr><h2 class="h6">بيانات مطالبة الجهة الضامنة</h2></div>
    <div class="col-md-4 d-none" data-payment-section="2,3"><label class="form-label">حالة الطلب *</label><select class="form-select" name="lawsuit_request_status_id"><option value="">اختر الحالة</option>@foreach($requestStatuses as $item)<option value="{{ $item->id }}">{{ $item->name_ar }}</option>@endforeach</select></div>
    <div class="col-md-4 d-none" data-payment-section="2,3"><label class="form-label">تاريخ الاستلام *</label><input class="form-control" type="date" name="received_date" value="{{ old('received_date') }}"></div>
    <div class="col-md-4 d-none" data-payment-section="2,3"><label class="form-label">سبب الرفض</label><select class="form-select" name="lawsuit_rejected_reason_id"><option value="">اختر السبب</option>@foreach($rejectedReasons as $item)<option value="{{ $item->id }}">{{ $item->name_ar }}</option>@endforeach</select></div>
    <div class="col-md-4 d-none" data-payment-section="2,3"><label class="form-label">المبلغ المغطى</label><input class="form-control" type="number" step="0.01" name="covered_amount" value="{{ old('covered_amount') }}"></div>
    <div class="col-md-4 d-none" data-payment-section="2,3"><label class="form-label">المبلغ غير المغطى</label><input class="form-control" type="number" step="0.01" name="uncovered_amount" value="{{ old('uncovered_amount') }}"></div>
    <div class="col-md-4 d-none" data-payment-section="2,3"><label class="form-label">تفاصيل الرفض</label><input class="form-control" name="rejected_reason" value="{{ old('rejected_reason') }}"></div>

    <div class="col-12"><hr><h2 class="h6">المرفقات</h2><p class="small text-muted">تُحفظ الملفات الأربعة المتاحة في النموذج القديم.</p></div>
    @for($i = 1; $i <= 4; $i++)<div class="col-md-3"><label class="form-label">الملف {{ $i }}</label><input class="form-control" type="file" name="file_{{ $i }}"></div>@endfor
</div>
<div class="card-footer text-start"><button class="btn btn-primary"><i class="bi bi-check2"></i> حفظ المطالبة</button></div>
</form>
@endsection
@push('scripts')
<script>
(() => {
 const payment = document.getElementById('payment-type');
 const setSections = () => document.querySelectorAll('[data-payment-section]').forEach(el => {
   const show = el.dataset.paymentSection.split(',').includes(payment.value); el.classList.toggle('d-none', !show);
   el.querySelectorAll('input,select').forEach(input => { input.required = show && ((payment.value === '1' && ['amount_paid','amount_rest'].includes(input.name)) || (['2','3'].includes(payment.value) && ['lawsuit_request_status_id','received_date'].includes(input.name))); });
 });
 payment.addEventListener('change', setSections); setSections();
 const file = document.getElementById('file-number');
 const fields = {patient_name_ar:'patient-name', patient_idno:'patient-idno', patient_nationality:'patient-nationality', contractor_name_ar:'liable-name', contractor_idno:'liable-idno', contractor_nationality:'liable-nationality', contractor_mobile:'liable-mobile', date_type:'date-type', birth_day:'birth-day', birth_month:'birth-month', birth_year:'birth-year', sexCode:'sex-code', contractor_approval_date:'contractor-approval-date'};
 let timer; file.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(async () => { if (!file.value.trim()) return; const response = await fetch(`{{ route('modules.legal-claims.payment-guarantee') }}?file_number=${encodeURIComponent(file.value)}`, {headers:{Accept:'application/json'}}); if (!response.ok) return; const row = (await response.json()).data; if (!row) return; Object.entries(fields).forEach(([source, target]) => { const node = document.getElementById(target); if (node && row[source] !== null && row[source] !== '') node.value = row[source]; }); }, 350); });
})();
</script>
@endpush
