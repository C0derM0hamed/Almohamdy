@extends('layouts.app')

@php
    $employeeMode = $employeeMode ?? false;
@endphp
@section('title', $employeeMode ? ($row ? 'تعديل تقرير الموظفين' : 'تقرير موظفين جديد') : ($row ? 'تعديل تقرير التنويم' : 'تقرير تنويم جديد'))

@php
    $existingEntries = collect($row->entries ?? [])->map(fn ($item) => (array) $item)->values()->all();
    $existingSupport = collect($row->support_services ?? [])->map(fn ($item) => (array) $item)->values()->all();
    $entryRows = $existingEntries !== [] ? $existingEntries : [[]];
    $supportRows = $existingSupport !== [] ? $existingSupport : [[]];
    $attendance = $row->attendance ?? null;
    $dateValue = static fn ($value): string => is_numeric($value) ? date('Y-m-d', (int) $value) : (string) $value;
@endphp

@section('content')
<div class="container-fluid py-3" dir="rtl">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h1 class="h4">{{ $employeeMode ? ($row ? 'تعديل تقرير الموظفين' : 'تقرير موظفين جديد') : ($row ? 'تعديل تقرير التنويم' : 'تقرير تنويم جديد') }}</h1>
            <p class="text-muted">الفترة والحضور والمرضى والخدمات المساندة والمرفقات محفوظة بنفس علاقات التقرير القديم.</p>
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

            <form method="post" enctype="multipart/form-data" action="{{ $employeeMode ? ($row ? route('modules.admission-inpatient.employee-report9.update', $row->id) : route('modules.admission-inpatient.employee-report9.store')) : ($row ? route('modules.admission-inpatient.report9.update', $row->id) : route('modules.admission-inpatient.report9.store')) }}">
                @csrf
                @if($row) @method('PUT') @endif

                @unless($employeeMode)
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><label class="form-label">{{ app()->getLocale() === 'ar' ? 'الفترة' : 'Period' }}</label><select class="form-select" name="period_id" required><option value="">{{ app()->getLocale() === 'ar' ? 'اختر' : 'Choose' }}</option>@foreach($periods as $period)<option value="{{ $period->id }}" @selected((int) old('period_id', $row->period ?? 0) === (int) $period->id)>{{ \App\Support\LocaleText::localizedValue($period->name_ar ?? null, $period->name_en ?? null) }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">التاريخ</label><input class="form-control" type="date" name="date" required value="{{ old('date', $dateValue($row->date ?? date('Y-m-d'))) }}"></div>
                    <div class="col-md-4"><label class="form-label">{{ app()->getLocale() === 'ar' ? 'موقع التقرير' : 'Report location' }}</label><select class="form-select" name="rep_place"><option value="">{{ app()->getLocale() === 'ar' ? 'اختر' : 'Choose' }}</option>@foreach($places as $place)<option value="{{ $place->id }}" @selected((int) old('rep_place', $row->rep_place ?? 0) === (int) $place->id)>{{ \App\Support\LocaleText::localizedValue($place->name_ar ?? null, $place->name_en ?? null) }}</option>@endforeach</select></div>
                </div>
                @endunless

                <div class="row g-3 mb-4">
                    <div class="col-md-3"><label class="form-label">عدد الحضور</label><input class="form-control" type="number" min="0" name="attendees" required value="{{ old('attendees', $attendance->attendees ?? 0) }}"></div>
                    <div class="col-md-3"><label class="form-label">عدد الغياب</label><input class="form-control" type="number" min="0" name="absence" required value="{{ old('absence', $attendance->absence ?? 0) }}"></div>
                    <div class="col-md-3"><label class="form-label">عدد المتأخرين</label><input class="form-control" type="number" min="0" name="latecomers" required value="{{ old('latecomers', $attendance->latecomers ?? 0) }}"></div>
                    <div class="col-md-3"><label class="form-label">عدد الاستئذانات</label><input class="form-control" type="number" min="0" name="permissible" required value="{{ old('permissible', $attendance->permissible ?? 0) }}"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h6 mb-0">بيانات المرضى</h2><button type="button" class="btn btn-sm btn-outline-primary" id="add-entry">إضافة صف</button></div>
                <div id="entries">
                    @foreach($entryRows as $index => $entry)
                        <div class="entry-row border rounded p-3 mb-2">
                            <input type="hidden" name="entries[{{ $index }}][existing_file]" value="{{ $entry['files'] ?? '' }}">
                            <div class="row g-2">
                                <div class="col-md-2"><input class="form-control" type="date" name="entries[{{ $index }}][date]" value="{{ old('entries.'.$index.'.date', $dateValue($entry['date'] ?? '')) }}"></div>
                                <div class="col-md-2"><input class="form-control" name="entries[{{ $index }}][filenumber]" placeholder="رقم الملف" value="{{ old('entries.'.$index.'.filenumber', $entry['filenumber'] ?? '') }}"></div>
                                <div class="col-md-2"><select class="form-select" name="entries[{{ $index }}][location]"><option value="">{{ app()->getLocale() === 'ar' ? 'الموقع/القسم' : 'Location/department' }}</option>@foreach($departments as $item)<option value="{{ $item->id }}" @selected((int) ($entry['location'] ?? 0) === (int) $item->id)>{{ \App\Support\LocaleText::localizedValue($item->name_ar ?? null, $item->name_en ?? null) }}</option>@endforeach</select></div>
                                <div class="col-md-2"><input class="form-control" name="entries[{{ $index }}][room_bod_number]" placeholder="الغرفة/السرير" value="{{ old('entries.'.$index.'.room_bod_number', $entry['room_bod_number'] ?? '') }}"></div>
                                <div class="col-md-2"><select class="form-select" name="entries[{{ $index }}][section]"><option value="">{{ app()->getLocale() === 'ar' ? 'القسم' : 'Department' }}</option>@foreach($sections as $item)<option value="{{ $item->id }}" @selected((int) ($entry['section'] ?? 0) === (int) $item->id)>{{ \App\Support\LocaleText::localizedValue($item->name_ar ?? null, $item->name_en ?? null) }}</option>@endforeach</select></div>
                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-entry">حذف</button></div>
                                <div class="col-md-5"><select class="form-select" name="entries[{{ $index }}][notice]"><option value="">{{ app()->getLocale() === 'ar' ? 'الملاحظة' : 'Notice' }}</option>@foreach($notices as $item)<option value="{{ $item->id }}" @selected((int) ($entry['notice'] ?? 0) === (int) $item->id)>{{ \App\Support\LocaleText::localizedValue($item->name_ar ?? null, $item->name_en ?? null) }}</option>@endforeach</select></div>
                                <div class="col-md-5"><select class="form-select" name="entries[{{ $index }}][action]"><option value="">{{ app()->getLocale() === 'ar' ? 'الإجراء' : 'Action' }}</option>@foreach($actions as $item)<option value="{{ $item->id }}" @selected((int) ($entry['action'] ?? 0) === (int) $item->id)>{{ \App\Support\LocaleText::localizedValue($item->name_ar ?? null, $item->name_en ?? null) }}</option>@endforeach</select></div>
                                <div class="col-md-2"><input class="form-control" name="entries[{{ $index }}][other]" placeholder="أخرى" value="{{ old('entries.'.$index.'.other', $entry['other'] ?? '') }}"></div>
                                <div class="col-12"><input class="form-control" type="file" accept=".jpg,.jpeg,.png,.gif" name="entries[{{ $index }}][file]">@if(!empty($entry['files']))<small class="text-muted">يوجد مرفق محفوظ وسيظل موجودًا إذا لم يتم استبداله.</small>@endif</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2 mt-4"><h2 class="h6 mb-0">الخدمات المساندة</h2><button type="button" class="btn btn-sm btn-outline-primary" id="add-support">إضافة خدمة</button></div>
                <div id="support-services">
                    @foreach($supportRows as $index => $entry)
                        <div class="support-row border rounded p-3 mb-2">
                            <input type="hidden" name="support_services[{{ $index }}][existing_file]" value="{{ $entry['files'] ?? '' }}">
                            <div class="row g-2">
                                <div class="col-md-2"><input class="form-control" type="date" name="support_services[{{ $index }}][date]" value="{{ old('support_services.'.$index.'.date', $dateValue($entry['date'] ?? '')) }}"></div>
                                <div class="col-md-3"><select class="form-select" name="support_services[{{ $index }}][maintenance_departments]"><option value="">{{ app()->getLocale() === 'ar' ? 'الإدارة' : 'Department' }}</option>@foreach($maintenanceDepartments as $item)<option value="{{ $item->id }}" @selected((int) ($entry['maintenance_departments'] ?? 0) === (int) $item->id)>{{ \App\Support\LocaleText::localizedValue($item->name_ar ?? null, $item->name_en ?? null) }}</option>@endforeach</select></div>
                                <div class="col-md-3"><select class="form-select" name="support_services[{{ $index }}][maintenance_type]"><option value="">{{ app()->getLocale() === 'ar' ? 'نوع الخدمة' : 'Service type' }}</option>@foreach($maintenanceTypes as $item)<option value="{{ $item->id }}" @selected((int) ($entry['maintenance_type'] ?? 0) === (int) $item->id)>{{ \App\Support\LocaleText::localizedValue($item->name_ar ?? null, $item->name_en ?? null) }}</option>@endforeach</select></div>
                                <div class="col-md-2"><select class="form-select" name="support_services[{{ $index }}][maintenance_request_type]"><option value="">{{ app()->getLocale() === 'ar' ? 'نوع الطلب' : 'Request type' }}</option>@foreach($requestTypes as $item)<option value="{{ $item->id }}" @selected((int) ($entry['maintenance_request_type'] ?? 0) === (int) $item->id)>{{ \App\Support\LocaleText::localizedValue($item->name_ar ?? null, $item->name_en ?? null) }}</option>@endforeach</select></div>
                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-support">حذف</button></div>
                                <div class="col-12"><textarea class="form-control" name="support_services[{{ $index }}][description]" placeholder="الوصف">{{ old('support_services.'.$index.'.description', $entry['description'] ?? '') }}</textarea><input class="form-control mt-2" type="file" accept=".jpg,.jpeg,.png,.gif" name="support_services[{{ $index }}][file]">@if(!empty($entry['files']))<small class="text-muted">يوجد مرفق محفوظ وسيظل موجودًا إذا لم يتم استبداله.</small>@endif</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button class="btn btn-primary mt-4">حفظ التقرير</button>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const entries = document.getElementById('entries');
    const support = document.getElementById('support-services');
    let entryIndex = entries.querySelectorAll('.entry-row').length;
    let supportIndex = support.querySelectorAll('.support-row').length;
    const bind = () => {
        entries.querySelectorAll('.remove-entry').forEach((button) => button.onclick = () => { if (entries.querySelectorAll('.entry-row').length > 1) button.closest('.entry-row').remove(); });
        support.querySelectorAll('.remove-support').forEach((button) => button.onclick = () => { if (support.querySelectorAll('.support-row').length > 1) button.closest('.support-row').remove(); });
    };
    document.getElementById('add-entry').onclick = () => { const row = entries.querySelector('.entry-row').cloneNode(true); row.querySelectorAll('input,select,textarea').forEach((el) => { el.name = el.name.replace(/entries\[\d+\]/, `entries[${entryIndex}]`); if (el.type !== 'file') el.value = ''; }); entries.appendChild(row); entryIndex++; bind(); };
    document.getElementById('add-support').onclick = () => { const row = support.querySelector('.support-row').cloneNode(true); row.querySelectorAll('input,select,textarea').forEach((el) => { el.name = el.name.replace(/support_services\[\d+\]/, `support_services[${supportIndex}]`); if (el.type !== 'file') el.value = ''; }); support.appendChild(row); supportIndex++; bind(); };
    bind();
})();
</script>
@endsection
