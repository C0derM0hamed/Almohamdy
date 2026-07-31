@extends('layouts.public-reply')

@section('title', __('correspondence.department_reply.title'))

@section('content')
    <div class="hm-public-reply__card">
        <h1>{{ __('correspondence.department_reply.title') }}</h1>
        <p class="subtitle">{{ __('correspondence.department_reply.subtitle') }}</p>

        <div class="hm-public-reply__meta">
            <div class="hm-public-reply__meta-item">
                <span>{{ __('correspondence.fields.authority') }}</span>
                <strong>{{ $item->authority?->localizedName() ?: '—' }}</strong>
            </div>
            <div class="hm-public-reply__meta-item">
                <span>{{ __('correspondence.fields.section') }}</span>
                <strong>{{ $item->section?->localizedName() ?: '—' }}</strong>
            </div>
            <div class="hm-public-reply__meta-item" style="grid-column: 1 / -1;">
                <span>{{ __('correspondence.fields.subject') }}</span>
                <strong>{{ $item->subject() ?: '—' }}</strong>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('public.correspondence.reply.store', ['token' => $token]) }}" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="mb-3">
                <label class="form-label" for="details">{{ __('correspondence.department_reply.details') }}</label>
                <textarea id="details" name="details" rows="5" class="form-control" required>{{ old('details') }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="files">{{ __('correspondence.department_reply.files') }}</label>
                <input id="files" type="file" name="files[]" class="form-control" multiple>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirm" id="confirm" value="1" required>
                <label class="form-check-label" for="confirm">{{ __('correspondence.department_reply.confirm') }}</label>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('correspondence.department_reply.submit') }}</button>
        </form>
    </div>
@endsection
