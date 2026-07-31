@extends('layouts.public-reply')

@section('title', __('inspection_visits.department_reply.title'))

@section('content')
    <div class="hm-public-reply__card">
        <h1>{{ $mode === 'returned' ? __('inspection_visits.department_reply.returned_title') : __('inspection_visits.department_reply.title') }}</h1>
        <p class="subtitle">{{ __('inspection_visits.department_reply.subtitle') }}</p>

        <div class="hm-public-reply__meta">
            <div class="hm-public-reply__meta-item">
                <span>{{ __('inspection_visits.fields.authority') }}</span>
                <strong>{{ $visit->authority?->localizedName() ?: '—' }}</strong>
            </div>
            <div class="hm-public-reply__meta-item">
                <span>{{ __('inspection_visits.fields.section') }}</span>
                <strong>{{ $visit->section?->localizedName() ?: '—' }}</strong>
            </div>
            <div class="hm-public-reply__meta-item">
                <span>{{ __('inspection_visits.fields.visit_date') }}</span>
                <strong>{{ optional($visit->visit_date)->format('Y-m-d') ?: '—' }}</strong>
            </div>
            <div class="hm-public-reply__meta-item">
                <span>{{ __('inspection_visits.fields.reply_time') }}</span>
                <strong>{{ optional($visit->reply_time)->format('Y-m-d H:i') ?: '—' }}</strong>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST"
              action="{{ $mode === 'returned'
                    ? route('public.inspection-visits.reply-returned.store', ['token' => $token])
                    : route('public.inspection-visits.reply.store', ['token' => $token]) }}"
              enctype="multipart/form-data"
              novalidate>
            @csrf

            @foreach ($pending as $index => $item)
                @php
                    $isReturned = $mode === 'returned';
                    $title = $isReturned
                        ? ($item->finding?->abuse_note_title ?: __('inspection_visits.department_reply.finding'))
                        : ($item->abuse_note_title ?: __('inspection_visits.department_reply.finding'));
                    $typeLabel = $isReturned
                        ? ($item->finding?->isViolation() ? __('inspection_visits.fields.violation') : __('inspection_visits.fields.note'))
                        : ($item->isViolation() ? __('inspection_visits.fields.violation') : __('inspection_visits.fields.note'));
                @endphp
                <div class="hm-public-reply__finding">
                    <h2>{{ $typeLabel }}: {{ $title }}</h2>
                    @if ($isReturned && filled($item->reason))
                        <p class="text-muted mb-2"><strong>{{ __('inspection_visits.department_reply.return_reason') }}:</strong> {{ $item->reason }}</p>
                    @endif
                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                    <div class="mb-2">
                        <label class="form-label" for="reply_{{ $index }}">{{ __('inspection_visits.department_reply.reply') }}</label>
                        <textarea id="reply_{{ $index }}" name="items[{{ $index }}][reply]" rows="3" class="form-control" required>{{ old("items.$index.reply") }}</textarea>
                    </div>
                    <div>
                        <label class="form-label" for="file_{{ $index }}">{{ __('inspection_visits.department_reply.evidence') }}</label>
                        <input id="file_{{ $index }}" type="file" name="files[{{ $index }}]" class="form-control">
                    </div>
                </div>
            @endforeach

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirm" id="confirm" value="1" @checked(old('confirm')) required>
                <label class="form-check-label" for="confirm">{{ __('inspection_visits.department_reply.confirm') }}</label>
            </div>

            <button type="submit" class="btn btn-primary">{{ __('inspection_visits.department_reply.submit') }}</button>
        </form>
    </div>
@endsection
