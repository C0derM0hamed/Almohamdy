@extends('layouts.public-reply')

@section('title', __('data_requests.department_reply.title'))

@section('content')
    <div class="hm-public-reply__card">
        <h1>{{ __('data_requests.department_reply.title') }}</h1>
        <p class="subtitle">{{ __('data_requests.department_reply.subtitle') }}</p>

        <div class="hm-public-reply__meta">
            <div class="hm-public-reply__meta-item">
                <span>{{ __('data_requests.fields.entity') }}</span>
                <strong>{{ $item->entity?->localizedName() ?: '—' }}</strong>
            </div>
            <div class="hm-public-reply__meta-item">
                <span>{{ __('data_requests.fields.section') }}</span>
                <strong>{{ $item->section?->localizedName() ?: '—' }}</strong>
            </div>
            <div class="hm-public-reply__meta-item" style="grid-column: 1 / -1;">
                <span>{{ __('data_requests.fields.subject') }}</span>
                <strong>{{ $item->subject() ?: '—' }}</strong>
            </div>
            @if (filled($item->becuse))
                <div class="hm-public-reply__meta-item" style="grid-column: 1 / -1;">
                    <span>{{ __('data_requests.department_reply.return_reason') }}</span>
                    <strong>{{ $item->becuse }}</strong>
                </div>
            @endif
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('public.data-requests.reply.store', ['token' => $token]) }}" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="mb-3">
                <label class="form-label" for="answer_text">{{ __('data_requests.department_reply.answer') }}</label>
                <textarea id="answer_text" name="answer_text" rows="5" class="form-control" required>{{ old('answer_text') }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="files">{{ __('data_requests.department_reply.files') }}</label>
                <input id="files" type="file" name="files[]" class="form-control" multiple>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirm" id="confirm" value="1" required>
                <label class="form-check-label" for="confirm">{{ __('data_requests.department_reply.confirm') }}</label>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('data_requests.department_reply.submit') }}</button>
        </form>
    </div>
@endsection
