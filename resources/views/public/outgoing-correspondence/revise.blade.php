@extends('layouts.public-reply')

@section('title', __('outgoing_correspondence.department_revise.title'))

@section('content')
    <div class="hm-public-reply__card">
        <h1>{{ __('outgoing_correspondence.department_revise.title') }}</h1>
        <p class="subtitle">{{ __('outgoing_correspondence.department_revise.subtitle') }}</p>

        <div class="hm-public-reply__meta">
            <div class="hm-public-reply__meta-item">
                <span>{{ __('outgoing_correspondence.fields.authority') }}</span>
                <strong>{{ $item->authority?->localizedName() ?: '—' }}</strong>
            </div>
            <div class="hm-public-reply__meta-item">
                <span>{{ __('outgoing_correspondence.fields.recipient_name') }}</span>
                <strong>{{ $item->sender ?: '—' }}</strong>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('public.outgoing-correspondence.revise.store', ['token' => $token]) }}" novalidate>
            @csrf
            <div class="mb-3">
                <label class="form-label" for="subject">{{ __('outgoing_correspondence.fields.subject') }}</label>
                <input id="subject" type="text" name="subject" class="form-control" value="{{ old('subject', $item->subject()) }}" required maxlength="1000">
            </div>
            <div class="mb-3">
                <label class="form-label" for="letter_content">{{ __('outgoing_correspondence.fields.letter_content') }}</label>
                <textarea id="letter_content" name="letter_content" rows="10" class="form-control" required>{{ old('letter_content', $item->letter_content) }}</textarea>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirm" id="confirm" value="1" required>
                <label class="form-check-label" for="confirm">{{ __('outgoing_correspondence.department_revise.confirm') }}</label>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('outgoing_correspondence.department_revise.submit') }}</button>
        </form>
    </div>
@endsection
