@extends('layouts.app')
@section('title','تفاصيل إقرار التنويم')
@section('content')
<div class="container-fluid py-3" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4">إقرار التنويم #{{ $row->id }}</h1>
            <p class="text-muted">{{ $row->patient_name_ar ?: $row->patient_name_en }} — {{ $row->created_at }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('modules.admission-inpatient.consents.pdf', $row->id) }}">PDF</a>
            <a class="btn btn-outline-secondary" href="{{ route('modules.admission-inpatient.consents.timeline', $row->id) }}">الخط الزمني</a>
            @if(($row->token ?? '') && (int)($row->duty_manager_approval_status ?? 0) === 1)
                <a class="btn btn-outline-secondary" target="_blank" href="{{ route('legacy.hospital-admission-consent-contract-approval', ['id' => $row->token]) }}">رابط المتعهد</a>
            @endif
            <a class="btn btn-outline-primary" href="{{ route('modules.admission-inpatient.consents.edit', $row->id) }}">تعديل</a>
            <form method="post" action="{{ route('modules.admission-inpatient.consents.toggle', $row->id) }}">@csrf @method('PATCH')<button class="btn btn-outline-warning">{{ (int)($row->publish ?? 0) ? 'إلغاء النشر' : 'نشر' }}</button></form>
            <form method="post" action="{{ route('modules.admission-inpatient.consents.destroy', $row->id) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger">حذف</button></form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">اسم المريض</dt><dd class="col-sm-8">{{ $row->patient_name_ar ?: $row->patient_name_en }}</dd>
                        <dt class="col-sm-4">هوية المريض</dt><dd class="col-sm-8">{{ $row->patient_idno }}</dd>
                        <dt class="col-sm-4">رقم الملف</dt><dd class="col-sm-8">{{ $row->patient_file_number }}</dd>
                        <dt class="col-sm-4">المتعهد</dt><dd class="col-sm-8">{{ $row->contractor_name_ar ?: $row->contractor_name_en }}</dd>
                        <dt class="col-sm-4">هوية المتعهد</dt><dd class="col-sm-8">{{ $row->contractor_idno }}</dd>
                        <dt class="col-sm-4">جوال المتعهد</dt><dd class="col-sm-8">{{ $row->contractor_mobile }}</dd>
                        <dt class="col-sm-4">اللغة</dt><dd class="col-sm-8">{{ (int)($row->language ?? 1) === 2 ? 'English' : 'العربية' }}</dd>
                        <dt class="col-sm-4">عنوان الإقرار</dt><dd class="col-sm-8">{{ $row->title }}</dd>
                        <dt class="col-sm-4">قرار المناوبة</dt><dd class="col-sm-8">{{ (int)($row->duty_manager_approval_status ?? 0) === 1 ? 'موافق' : ((int)($row->duty_manager_approval_status ?? 0) === 2 ? 'غير موافق' : 'قيد المراجعة') }} @if($row->duty_manager_note) — {{ $row->duty_manager_note }} @endif</dd>
                        <dt class="col-sm-4">قرار المتعهد</dt><dd class="col-sm-8">{{ (int)($row->contract_approval_status ?? 0) === 1 ? 'موافق' : ((int)($row->contract_approval_status ?? 0) === 2 ? 'مرفوض' : 'قيد الانتظار') }} @if($row->contract_note) — {{ $row->contract_note }} @endif</dd>
                    </dl>
                    <hr>
                    <h2 class="h6">نص الإقرار</h2>
                    <div class="border rounded p-3" style="line-height:2">{!! nl2br(e($row->consent_content ?? 'لا يوجد نص مخصص.')) !!}</div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h2 class="h6">إعادة دعوة المتعهد للتوقيع</h2>
                    <p class="text-muted small">نفس إجراء ReInvition القديم، مع تمرير رقم مستند التصديق وبيانات الاتصال من خلال مسار آمن.</p>
                    <form method="post" action="{{ route('modules.admission-inpatient.consents.reminder') }}" class="row g-2">
                        @csrf
                        <div class="col-md-4"><label class="form-label">رقم المستند</label><input class="form-control" name="id" value="{{ $row->token ?? $row->reference_number ?? '' }}" required></div>
                        <div class="col-md-4"><label class="form-label">الجوال</label><input class="form-control" name="mo" value="{{ $row->contractor_mobile ?? '' }}" required></div>
                        <div class="col-md-4"><label class="form-label">البريد الإلكتروني</label><input type="email" class="form-control" name="em" value="{{ $row->email ?? '' }}"></div>
                        <div class="col-12"><button class="btn btn-outline-primary">إرسال تذكير التوقيع</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">قرار مدير المناوبة</h2>
                    <form method="post" action="{{ route('modules.admission-inpatient.consents.duty-decision', $row->id) }}">
                        @csrf
                        <select class="form-select mb-2" name="status" required>
                            <option value="1" @selected((int)($row->duty_manager_approval_status ?? 0) === 1)>موافقة</option>
                            <option value="2" @selected((int)($row->duty_manager_approval_status ?? 0) === 2)>رفض</option>
                        </select>
                        <textarea class="form-control mb-2" name="note" placeholder="ملاحظة">{{ $row->duty_manager_note }}</textarea>
                        <button class="btn btn-primary w-100">حفظ القرار</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
