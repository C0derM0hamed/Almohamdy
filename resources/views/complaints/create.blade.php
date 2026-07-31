@extends('layouts.app')

@section('title', __('complaints.create_title'))
@section('sidebar_heading', __('complaints.title'))
@section('sidebar_subheading', __('complaints.subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-complaints-redesign.css') }}?v={{ filemtime(public_path('css/hm-complaints-redesign.css')) }}" rel="stylesheet">
@endpush

@section('content')
<div class="hm-cp">
    <header class="cp-detail-head"><div><h1>{{ __('complaints.create_title') }}</h1><p>{{ __('complaints.create_subtitle') }}</p></div></header>
    <section class="cp-info-card">
        <form method="POST" action="{{ route('modules.complaints.store') }}" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="complainant_name">{{ __('complaints.fields.complainant') }}</label><input id="complainant_name" name="complainant_name" value="{{ old('complainant_name') }}" class="form-control @error('complainant_name') is-invalid @enderror" required>@error('complainant_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="patient_name">{{ __('complaints.fields.patient') }}</label><input id="patient_name" name="patient_name" value="{{ old('patient_name') }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label" for="mobile">{{ __('complaints.fields.mobile') }}</label><input id="mobile" name="mobile" value="{{ old('mobile') }}" class="form-control @error('mobile') is-invalid @enderror"></div>
                <div class="col-md-6"><label class="form-label" for="id_no">{{ __('complaints.fields.id_no') }}</label><input id="id_no" name="id_no" value="{{ old('id_no') }}" maxlength="12" class="form-control"></div>
                <div class="col-md-6"><label class="form-label" for="file_number">{{ __('complaints.fields.file_no') }}</label><input id="file_number" name="file_number" value="{{ old('file_number') }}" maxlength="20" class="form-control"></div>
                <div class="col-md-6"><label class="form-label" for="branches_departments_id">{{ __('complaints.fields.department') }}</label><select id="branches_departments_id" name="branches_departments_id" class="form-select @error('branches_departments_id') is-invalid @enderror" required><option value="">—</option>@foreach($departmentOptions as $department)<option value="{{ $department->id }}" @selected(old('branches_departments_id') == $department->id)>{{ $department->localizedName() }}</option>@endforeach</select>@error('branches_departments_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="defendant">{{ __('complaints.fields.defendant') }}</label><input id="defendant" name="defendant" value="{{ old('defendant') }}" class="form-control @error('defendant') is-invalid @enderror" required>@error('defendant')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="event_date">{{ __('complaints.fields.event_date') }}</label><input id="event_date" type="date" name="event_date" value="{{ old('event_date') }}" class="form-control"></div>
                <div class="col-12"><label class="form-label" for="details">{{ __('complaints.fields.details') }}</label><textarea id="details" name="details" rows="5" class="form-control @error('details') is-invalid @enderror" required>{{ old('details') }}</textarea>@error('details')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <input type="hidden" name="type" value="1">
                <div class="col-12"><label class="form-label" for="attachment">{{ __('complaints.fields.attachment') }}</label><input id="attachment" type="file" name="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf" class="form-control @error('attachment') is-invalid @enderror">@error('attachment')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
            <div class="mt-4 d-flex gap-2"><button class="cp-btn cp-btn--primary" type="submit">{{ __('complaints.save') }}</button><a class="cp-btn cp-btn--outline" href="{{ route('modules.complaints') }}">{{ __('complaints.cancel') }}</a></div>
        </form>
    </section>
</div>
@endsection
