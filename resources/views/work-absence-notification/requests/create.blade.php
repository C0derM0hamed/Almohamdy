@extends('layouts.app')
@section('title', __('work_absence_notification.request.create'))
@section('content')
<div class="hm-hs hm-wan"><h1>{{ __('work_absence_notification.request.create') }}</h1>
 @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <form method="POST" action="{{ route('modules.work-absence.requests.store') }}" enctype="multipart/form-data">@csrf
 <div class="mb-3"><label>{{ __('work_absence_notification.request.type') }}</label><select name="memo_types_id" required class="form-select"><option value="">—</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected(old('memo_types_id')==$type->id)>{{ $type->localizedName() }}</option>@endforeach</select></div>
 <div class="row"><div class="col-md-6 mb-3"><label>{{ __('work_absence_notification.request.begin') }}</label><input type="date" name="begin_date" value="{{ old('begin_date') }}" required class="form-control"></div><div class="col-md-6 mb-3"><label>{{ __('work_absence_notification.request.end') }}</label><input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control"></div></div>
 <div class="mb-3"><label>{{ __('work_absence_notification.request.relationship') }}</label><input name="relationship" value="{{ old('relationship') }}" class="form-control"></div>
 <div class="mb-3"><label>{{ __('work_absence_notification.request.deceased_relationship') }}</label><select name="deceased_relationship" class="form-select"><option value="">—</option>@foreach($deathCategories as $category)<option value="{{ $category->id }}">{{ app()->getLocale()==='ar' ? $category->name_ar : $category->name_en }}</option>@endforeach</select></div>
 <div class="mb-3"><label>{{ __('work_absence_notification.request.medical_authority') }}</label><input name="medical_authority" value="{{ old('medical_authority') }}" class="form-control"></div>
 <div class="mb-3"><label>{{ __('work_absence_notification.request.days') }}</label><input type="number" min="0" max="365" name="absence_days" value="{{ old('absence_days') }}" class="form-control"></div><div class="mb-3"><label>{{ __('work_absence_notification.request.reason') }}</label><textarea name="absence_reason" class="form-control">{{ old('absence_reason') }}</textarea></div>
 <div class="mb-3"><label>{{ __('work_absence_notification.request.document') }}</label><select name="document_status" class="form-select"><option value="2">{{ __('work_absence_notification.request.no_document') }}</option><option value="1">{{ __('work_absence_notification.request.has_document') }}</option></select><input type="file" name="sick_leave_file" class="form-control mt-2" accept=".jpg,.jpeg,.png,.gif,.pdf"></div>
 <div class="form-check mb-3"><input type="checkbox" name="acknowledgement" value="1" required class="form-check-input" id="ack"><label for="ack" class="form-check-label">{{ __('work_absence_notification.request.ack') }}</label></div><button class="hs-btn hs-btn--primary">{{ __('work_absence_notification.request.submit') }}</button>
 </form></div>
@endsection
