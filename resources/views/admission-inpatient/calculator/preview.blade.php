@extends('layouts.app')

@section('title', $mode === 'observation' ? 'حاسبة تحت الملاحظة' : 'حاسبة الإجراءات')

@section('content')
<div class="container-fluid py-3" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
        <div><h1 class="h4">{{ $mode === 'observation' ? 'حاسبة تحت الملاحظة' : 'حاسبة الإجراءات' }}</h1><p class="text-muted">حساب preview متوافق مع branch/admission calculator.php ولا ينشئ سجلًا.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('modules.admission-inpatient.calculator.index', 'standard') }}">سجل التسعيرات</a>
    </div>

    <form class="card border-0 shadow-sm mb-3" method="get">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label">حالة التنويم</label><select class="form-select" name="admission_status_id" onchange="this.form.submit()"><option value="1" @selected($status === 1)>تحت الملاحظة</option><option value="2" @selected($status === 2)>إجراءات</option></select></div>
        </div>
    </form>

    <form class="card border-0 shadow-sm" method="post" action="{{ route('modules.admission-inpatient.calculator.preview') }}">
        @csrf
        <input type="hidden" name="admission_status_id" value="{{ $status }}">
        <div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label">الجنسية</label><select class="form-select" name="nationality" required><option value="">اختر</option>@foreach($nationalities as $item)<option value="{{ $item->id }}" @selected((int)($input['nationality'] ?? 0) === (int)$item->id)>{{ $item->name_ar ?: $item->name_en }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">الغرفة</label><select class="form-select" name="room" required><option value="">اختر</option>@foreach($rooms as $item)<option value="{{ $item->id }}" @selected((int)($input['room'] ?? 0) === (int)$item->id)>{{ $item->name_ar ?: $item->name_en }} — {{ number_format((float)$item->price, 2) }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">عدد الأيام</label><input class="form-control" type="number" min="1" name="days" required value="{{ $input['days'] ?? 1 }}"></div>
            <div class="col-md-2"><label class="form-label">الخصم %</label><input class="form-control" type="number" min="0" max="50" step="0.01" name="discount" value="{{ $input['discount'] ?? 0 }}"></div>
            @if($mode === 'procedures')
                <div class="col-12"><label class="form-label">الإجراءات</label><select class="form-select" name="procedurs[]" multiple size="7">@foreach($servicePrices as $item)<option value="{{ $item->id }}" @selected(in_array((int)$item->id, array_map('intval', $input['procedurs'] ?? []), true))>{{ $item->code }} — {{ $item->name_ar ?: $item->name_en }} — {{ number_format((float)$item->price, 2) }}</option>@endforeach</select></div>
            @endif
        </div>
        <div class="card-footer"><button class="btn btn-primary">احسب</button></div>
    </form>

    @if($result)
        <div class="card border-0 shadow-sm mt-3"><div class="card-body"><h2 class="h5">نتيجة الحساب</h2><table class="table"><tr><th>سعر الغرفة لليوم</th><td>{{ number_format($result['room_price'], 2) }}</td></tr><tr><th>إجمالي الغرفة</th><td>{{ number_format($result['room_total'], 2) }}</td></tr><tr><th>إجمالي الإجراءات</th><td>{{ number_format($result['procedures_total'], 2) }}</td></tr><tr><th>بعد الخصم</th><td>{{ number_format($result['after_discount'], 2) }}</td></tr><tr><th>الضريبة</th><td>{{ number_format($result['vat'], 2) }} ({{ $result['vat_rate'] }}%)</td></tr><tr class="table-primary"><th>الإجمالي</th><td>{{ number_format($result['total'], 2) }}</td></tr></table></div></div>
    @endif
</div>
@endsection
