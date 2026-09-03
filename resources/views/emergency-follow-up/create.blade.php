@extends('layouts.app')

@section('title', __('emergency_follow_up.add'))

@section('content')
    <div class="row justify-content-center"><div class="col-xl-9">
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-4"><span class="hm-hope-stat-icon hm-hope-stat-icon--primary"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i></span><div><h1 class="h5 mb-1">{{ __('emergency_follow_up.add') }}</h1><p class="text-muted mb-0">{{ __('emergency_follow_up.subtitle') }}</p></div></div>
            <form method="post" action="{{ route('modules.emergency-follow-up.store') }}" novalidate>@csrf
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="file_number">{{ __('emergency_follow_up.file_number') }}</label><input class="form-control @error('file_number') is-invalid @enderror" id="file_number" name="file_number" type="number" min="1" value="{{ old('file_number') }}" required>@error('file_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label" for="notice">{{ __('emergency_follow_up.notice') }}</label><select class="form-select @error('notice') is-invalid @enderror" id="notice" name="notice" required><option value="">—</option>@foreach ($noticeTypes as $type)<option value="{{ $type->id }}" @selected(old('notice') == $type->id)>{{ \App\Support\LocaleText::localizedValue($type->name_ar ?? null, $type->name_en ?? null) }}</option>@endforeach</select>@error('notice')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label" for="notice_type">{{ __('emergency_follow_up.notice_type') }}</label><select class="form-select @error('notice_type') is-invalid @enderror" id="notice_type" name="notice_type" required><option value="">—</option><option value="1" @selected(old('notice_type') == 1)>{{ __('emergency_follow_up.urgent') }}</option><option value="2" @selected(old('notice_type') == 2)>{{ __('emergency_follow_up.non_urgent') }}</option></select>@error('notice_type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label" for="status">{{ __('emergency_follow_up.status') }}</label><select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required><option value="2" @selected(old('status', 2) == 2)>{{ __('emergency_follow_up.open_status') }}</option><option value="1" @selected(old('status') == 1)>{{ __('emergency_follow_up.closed_status') }}</option></select>@error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12"><label class="form-label" for="description">{{ __('emergency_follow_up.description') }}</label><input class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description') }}" maxlength="255" required>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12"><label class="form-label" for="action">{{ __('emergency_follow_up.action') }}</label><input class="form-control @error('action') is-invalid @enderror" id="action" name="action" value="{{ old('action') }}" maxlength="255" required>@error('action')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>{{ __('emergency_follow_up.save') }}</button><a class="btn btn-light" href="{{ route('modules.emergency-follow-up.index') }}">{{ __('emergency_follow_up.cancel') }}</a></div>
            </form>
        </div></div>
    </div></div>
@endsection
