@extends('layouts.app')

@section('title', __('licenses.create'))
@section('sidebar_heading', __('licenses.title'))
@section('sidebar_subheading', __('licenses.subtitle'))

@push('styles')
<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $url = static fn ($name, $params = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : '#';
@endphp
<div class="hm-licenses">
    @include('licenses.partials.page-header', [
        'title' => __('licenses.create'), 'subtitle' => __('licenses.subtitle'), 'icon' => 'bi-file-earmark-plus',
        'actions' => new \Illuminate\Support\HtmlString('<a class="lic-btn" href="'.e($url('modules.licenses.index')).'"><i class="bi bi-arrow-left"></i>'.e(__('licenses.back')).'</a>'),
    ])
    @include('licenses.partials.feedback')
    <section class="lic-panel" aria-labelledby="licenseFormTitle">
        <h2 id="licenseFormTitle" class="lic-panel__title"><i class="bi bi-card-checklist" aria-hidden="true"></i>{{ __('licenses.sections.summary') }}</h2>
        <form method="POST" action="{{ $url('modules.licenses.store') }}" enctype="multipart/form-data" novalidate>
            @csrf
            @include('licenses._form', ['includeAttachments' => true])
            <div class="lic-form-actions">
                <button class="lic-btn lic-btn--primary" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i>{{ __('licenses.save') }}</button>
                <a class="lic-btn" href="{{ $url('modules.licenses.index') }}">{{ __('licenses.cancel') }}</a>
            </div>
        </form>
    </section>
</div>
@endsection
