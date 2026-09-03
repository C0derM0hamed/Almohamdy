@extends('layouts.app')
@section('title', ($row ? 'تعديل' : 'إضافة').' '.$spec['title'])
@section('content')
<div class="container-fluid py-3" dir="rtl"><div class="card border-0 shadow-sm"><div class="card-body"><h1 class="h4 mb-4">{{ $row ? 'تعديل' : 'إضافة' }} {{ $spec['title'] }}</h1><form method="post" action="{{ $row ? route('modules.admission-inpatient.reference.update',[$type,$row->id]) : route('modules.admission-inpatient.reference.store',$type) }}">@csrf @if($row) @method('PUT') @endif<div class="row g-3">
@foreach($spec['fields'] as $field)
    @if($field === 'admission_status_id')<div class="col-md-6"><label class="form-label">حالة التنويم</label><select class="form-select" name="{{ $field }}" required><option value="">اختر</option>@foreach($options['statuses'] ?? [] as $status)<option value="{{ $status->id }}" @selected((string)old($field,$row->{$field} ?? '') === (string)$status->id)>{{ $status->name_ar ?: $status->name_en }}</option>@endforeach</select></div>
    @elseif($field === 'section_id')<div class="col-md-6"><label class="form-label">القسم</label><select class="form-select" name="{{ $field }}" required><option value="">اختر</option>@foreach($options['sections'] ?? [] as $section)<option value="{{ $section->id }}" @selected((string)old($field,$row->{$field} ?? '') === (string)$section->id)>{{ $section->name_ar ?: $section->name_en }}</option>@endforeach</select></div>
    @elseif($field === 'price')<div class="col-md-6"><label class="form-label">السعر</label><input class="form-control" type="number" step="0.01" min="0" name="{{ $field }}" value="{{ old($field,$row->{$field} ?? '') }}"></div>
    @else<div class="col-md-6"><label class="form-label">{{ $spec['labels'][$field] ?? $field }}</label><input class="form-control" name="{{ $field }}" value="{{ old($field,$row->{$field} ?? '') }}" @if($field === 'name_en' && in_array($type,['nationalities','statuses','rooms','service-prices'],true)) required @endif></div>@endif
@endforeach
</div><div class="mt-4 d-flex gap-2"><button class="btn btn-primary">حفظ</button><a class="btn btn-outline-secondary" href="{{ route('modules.admission-inpatient.reference.index',$type) }}">إلغاء</a></div></form></div></div></div>
@endsection
