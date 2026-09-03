@extends('layouts.app')
@php($pageTitle = $mode === 'observation' ? 'تسعيرة تحت الملاحظة' : ($mode === 'procedures' ? 'تسعيرة بالإجراءات' : 'تسعيرة التنويم'))
@php($manualProcedures = $type === 'manual' && $mode === 'procedures')
@section('title', $pageTitle)
@section('figma_page_header', 'true')
@section('content')
<div class="container-fluid py-3" dir="rtl"><div class="card border-0 shadow-sm"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h4 mb-1">{{ $pageTitle }}</h1><p class="text-muted mb-0">{{ $type === 'manual' ? 'الحاسبة اليدوية' : 'الحاسبة القياسية' }}</p></div><a class="btn btn-outline-secondary" href="{{ route('modules.admission-inpatient.calculator.index', $type) }}">رجوع</a></div>
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="post" action="{{ $row ? route('modules.admission-inpatient.calculator.update', [$type, $row->id]) : route('modules.admission-inpatient.calculator.store', [$type, $mode]) }}">@csrf @if($row) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">اسم المريض</label><input class="form-control" name="patient_name" required value="{{ old('patient_name', $row->patient_name ?? '') }}"></div>
            <div class="col-md-6"><label class="form-label">رقم الملف</label><input class="form-control" name="file_number" required value="{{ old('file_number', $row->file_number ?? '') }}"></div>
            <div class="col-md-4"><label class="form-label">الجنسية</label><select class="form-select" name="nationality" required><option value="">اختر</option>@foreach($nationalities as $item)<option value="{{ $item->id }}" @selected((string) old('nationality', $row->nationality ?? '') === (string) $item->id)>{{ $item->name_ar ?: $item->name_en }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">الغرفة</label><select class="form-select" name="room" @if(!$manualProcedures) required @endif><option value="">{{ $manualProcedures ? 'بدون غرفة' : 'اختر' }}</option>@foreach($rooms as $item)<option value="{{ $item->id }}" @selected((string) old('room', $row->room ?? '') === (string) $item->id)>{{ $item->name_ar ?: $item->name_en }} — {{ number_format((float) $item->price, 2) }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">الطبيب المعالج</label><input class="form-control" name="doctor" required value="{{ old('doctor', $row->doctor ?? '') }}"></div>
            <div class="col-md-4"><label class="form-label">عدد الأيام</label><input class="form-control" type="number" min="1" name="days" @if(!$manualProcedures) required @endif value="{{ old('days', $row->days ?? ($manualProcedures ? 0 : 1)) }}"></div>
            <div class="col-md-4"><label class="form-label">نوع الغرفة</label><select class="form-select" name="room_type"><option value="">اختر</option>@foreach($roomTypes ?? [] as $item)<option value="{{ $item->id }}" @selected((string)old('room_type',$row->room_type ?? '') === (string)$item->id)>{{ $item->name_ar ?: $item->name_en }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">نسبة الخصم</label><input class="form-control" type="number" min="0" max="50" step="0.01" name="discount" value="{{ old('discount', $row->discount ?? 0) }}"></div>
            <div class="col-md-4"><label class="form-label">قيمة الأدوات</label><input class="form-control" type="number" min="0" step="0.01" name="tools_value" value="{{ old('tools_value', $row->tools_value ?? 0) }}"></div>
            @if($mode === 'procedures' && !$manualProcedures)
                @php($selectedProcedureCodes = collect(explode('-', (string)($row->procedurs ?? '')))->filter())
                <div class="col-12"><label class="form-label">الإجراءات</label><select class="form-select" name="procedurs[]" multiple size="7">@foreach($servicePrices as $item)<option value="{{ $item->id }}" @selected($selectedProcedureCodes->contains((string)($item->code ?? '')))>{{ $item->code }} — {{ $item->name_ar ?: $item->name_en }} — {{ number_format((float) $item->price, 2) }}</option>@endforeach</select><small class="text-muted">يمكن اختيار أكثر من إجراء.</small></div>
            @elseif($manualProcedures)
                <div class="col-12"><div class="d-flex justify-content-between align-items-center mb-2"><label class="form-label mb-0">الإجراءات اليدوية</label><button type="button" class="btn btn-sm btn-outline-primary" id="add-manual-procedure">إضافة إجراء</button></div>
                    @php($manualRows = old('manual_procedures', collect($row->manual_procedures ?? [])->map(fn($p) => ['name' => $p->name ?? '', 'price' => $p->price ?? 0, 'pharmaceutical' => $p->pharmaceutical ?? 0])->all()))
                    @php($pharmaceuticalValue = old('pharmaceutical', collect($manualRows)->first()['pharmaceutical'] ?? 0))
                    <div class="row g-2 mb-3"><div class="col-md-6"><label class="form-label">قيمة الأدوية</label><input class="form-control" type="number" min="0" step="0.01" name="pharmaceutical" value="{{ $pharmaceuticalValue }}"></div></div>
                    <div id="manual-procedures">
                    @forelse($manualRows as $index => $procedure)<div class="row g-2 mb-2 manual-procedure-row"><div class="col-md-6"><input class="form-control" name="manual_procedures[{{ $index }}][name]" placeholder="اسم الإجراء" value="{{ $procedure['name'] ?? '' }}"></div><div class="col-md-4"><input class="form-control" type="number" min="0" step="0.01" name="manual_procedures[{{ $index }}][price]" placeholder="السعر" value="{{ $procedure['price'] ?? 0 }}"></div><div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-manual-procedure">حذف</button></div></div>@empty<div class="row g-2 mb-2 manual-procedure-row"><div class="col-md-6"><input class="form-control" name="manual_procedures[0][name]" placeholder="اسم الإجراء"></div><div class="col-md-4"><input class="form-control" type="number" min="0" step="0.01" name="manual_procedures[0][price]" placeholder="السعر" value="0"></div><div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-manual-procedure">حذف</button></div></div>@endforelse
                </div></div>
            @elseif($mode !== 'observation')
                <div class="col-12"><label class="form-label">رموز الإجراءات (اختياري)</label><input class="form-control" name="procedurs" value="{{ old('procedurs', $row->procedurs ?? '') }}"></div>
            @endif
            <div class="col-md-4"><label class="form-label">لغة النموذج</label><select class="form-select" name="lang"><option value="ar" @selected((string)old('lang', $row->lang ?? 'ar') === 'ar')>العربية</option><option value="en" @selected((string)old('lang', $row->lang ?? '') === 'en')>English</option></select></div>
        </div>
        <button class="btn btn-primary mt-4" type="submit"><i class="bi bi-check-lg"></i> حفظ التسعيرة</button>
    </form>
</div></div></div>
@if($manualProcedures)<script>
(() => { const box = document.getElementById('manual-procedures'); const add = document.getElementById('add-manual-procedure'); let index = box.querySelectorAll('.manual-procedure-row').length;
const bind = () => box.querySelectorAll('.remove-manual-procedure').forEach(button => button.onclick = () => { if (box.querySelectorAll('.manual-procedure-row').length > 1) button.closest('.manual-procedure-row').remove(); });
add.onclick = () => { const row = document.createElement('div'); row.className = 'row g-2 mb-2 manual-procedure-row'; row.innerHTML = `<div class="col-md-6"><input class="form-control" name="manual_procedures[${index}][name]" placeholder="اسم الإجراء"></div><div class="col-md-4"><input class="form-control" type="number" min="0" step="0.01" name="manual_procedures[${index}][price]" placeholder="السعر" value="0"></div><div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-manual-procedure">حذف</button></div>`; box.appendChild(row); index++; bind(); }; bind(); })();
</script>@endif
@endsection
