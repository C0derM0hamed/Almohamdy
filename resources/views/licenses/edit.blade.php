@extends('layouts.app')

@section('title', __('licenses.edit'))
@section('sidebar_heading', __('licenses.title'))
@section('sidebar_subheading', __('licenses.subtitle'))

@push('styles')
<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $url = static fn ($name, $params = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : '#';
    $recordId = $license->getRouteKey();
@endphp
<div class="hm-licenses">
    @include('licenses.partials.page-header', [
        'title' => __('licenses.edit'), 'subtitle' => $license->title ?: $license->license_number ?: '#'.$license->id, 'icon' => 'bi-pencil-square',
        'actions' => new \Illuminate\Support\HtmlString('<a class="lic-btn" href="'.e($url('modules.licenses.show', $recordId)).'"><i class="bi bi-arrow-left"></i>'.e(__('licenses.back')).'</a>'),
    ])
    @include('licenses.partials.feedback')
    <section class="lic-panel">
        <form method="POST" action="{{ $url('modules.licenses.update', $recordId) }}" novalidate>
            @csrf
            @method('PUT')
            @include('licenses._form')
            <div class="lic-form-actions">
                <button class="lic-btn lic-btn--primary" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i>{{ __('licenses.save_changes') }}</button>
                <a class="lic-btn" href="{{ $url('modules.licenses.show', $recordId) }}">{{ __('licenses.cancel') }}</a>
            </div>
        </form>
    </section>
</div>
@endsection
