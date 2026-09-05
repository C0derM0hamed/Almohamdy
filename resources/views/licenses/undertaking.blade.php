@extends('layouts.app')

@section('title', __('licenses.undertaking.title'))
@section('sidebar_heading', __('licenses.title'))
@section('sidebar_subheading', __('licenses.subtitle'))

@push('styles')
<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $url = static fn ($name, $params = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : '#';
    $record = $undertaking ?? $license->currentUndertaking ?? null;
    $undertakingText = old('undertaking_text', data_get($record, 'undertaking_text') ?: __('licenses.undertaking.text'));
    $recordId = $license->getRouteKey();
    $attachments = $license->attachments ?? collect();
@endphp
<div class="hm-licenses">
    @include('licenses.partials.feedback')
    <section class="lic-gate" aria-labelledby="undertakingGateTitle">
        <span class="lic-gate__icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
        <h1 id="undertakingGateTitle">{{ __('licenses.undertaking.gate_title') }}</h1>
        <p class="text-muted">{{ __('licenses.undertaking.gate_help') }}</p>
        <div class="lic-chip-list justify-content-center">
            <span class="lic-chip"><i class="bi bi-patch-check"></i>{{ $license->title ?: $license->license_number ?: '#'.$license->id }}</span>
            @if ($license->expiry_date)<span class="lic-chip"><i class="bi bi-calendar-event"></i>{{ __('licenses.fields.expiry_date') }}: {{ substr((string) $license->expiry_date, 0, 10) }}</span>@endif
        </div>
        <blockquote class="lic-gate__text">{{ $undertakingText }}</blockquote>
        <div class="lic-alert lic-alert--warning" role="note"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> {{ __('licenses.undertaking.blocking_notice') }}</div>

        @if ($attachments->isNotEmpty())
            <section class="lic-panel mt-4 text-start" aria-labelledby="undertakingAttachmentsTitle">
                <h2 id="undertakingAttachmentsTitle" class="lic-panel__title"><i class="bi bi-paperclip"></i>{{ __('licenses.undertaking.attachments_title') }}</h2>
                <p class="lic-help mb-3">{{ __('licenses.undertaking.attachments_help') }}</p>
                @include('licenses.partials.file-cards', [
                    'files' => $attachments->sortByDesc('id'),
                    'empty' => __('licenses.attachments.empty'),
                    'downloadUrl' => fn ($file) => $url('modules.licenses.attachments.download', [$recordId, $file->getRouteKey()]),
                    'subtitle' => fn ($file) => $file->description,
                    'nameAsLink' => true,
                ])
            </section>
        @endif

        <form method="POST" action="{{ $url('modules.licenses.undertaking.accept', $recordId) }}" class="mt-4">
            @csrf
            <input type="hidden" name="undertaking_id" value="{{ data_get($record, 'id') }}">
            <div class="lic-gate__check">
                <input id="undertaking_confirm" type="checkbox" name="accept_undertaking" value="1" required @checked(old('accept_undertaking')) class="form-check-input @error('accept_undertaking') is-invalid @enderror">
                <label for="undertaking_confirm">{{ __('licenses.undertaking.confirm') }}</label>
            </div>
            @error('accept_undertaking')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
            <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                <button type="submit" class="lic-btn lic-btn--primary"><i class="bi bi-check2-circle"></i>{{ __('licenses.undertaking.accept') }}</button>
            </div>
        </form>

        <form method="POST" action="{{ $url('modules.licenses.undertaking.reject', $recordId) }}" class="mt-4" onsubmit="return confirm(@json(__('licenses.undertaking.reject_confirm')));">
            @csrf
            <div class="lic-field text-start">
                <label for="rejection_reason">{{ __('licenses.undertaking.reject_reason') }}</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="3" maxlength="2000" class="form-control @error('rejection_reason') is-invalid @enderror" placeholder="{{ __('licenses.undertaking.reject_reason_placeholder') }}">{{ old('rejection_reason') }}</textarea>
                @error('rejection_reason')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="lic-btn lic-btn--danger mt-2"><i class="bi bi-x-circle"></i>{{ __('licenses.undertaking.reject') }}</button>
        </form>
    </section>
</div>
@endsection
