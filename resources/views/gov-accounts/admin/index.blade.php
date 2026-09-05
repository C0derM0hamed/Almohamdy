@extends('layouts.app')

@section('title', __('gov_accounts.admin.title'))
@section('sidebar_heading', __('gov_accounts.title'))
@section('sidebar_subheading', __('gov_accounts.admin.subtitle'))

@push('styles')
<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php $url = static fn ($name, $params = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : '#'; @endphp
<div class="hm-licenses">
    @include('licenses.partials.page-header', ['title' => __('gov_accounts.admin.title'), 'subtitle' => __('gov_accounts.admin.subtitle'), 'icon' => 'bi-sliders', 'actions' => new \Illuminate\Support\HtmlString('<a class="lic-btn" href="'.e($url('modules.gov-accounts.dashboard')).'"><i class="bi bi-arrow-left"></i>'.e(__('gov_accounts.actions.back')).'</a>')])
    @include('licenses.partials.feedback')
    <section class="lic-admin-grid" aria-label="{{ __('gov_accounts.admin.title') }}">
        @foreach ([
            ['authorities', 'bi-bank', $counts['authorities'] ?? 0],
            ['services', 'bi-grid-1x2', $counts['services'] ?? 0],
            ['roles', 'bi-person-badge', $counts['roles'] ?? 0],
            ['department-heads', 'bi-diagram-3', $counts['department-heads'] ?? 0],
        ] as [$key, $icon, $count])
            <a class="lic-admin-card" href="{{ $url('modules.gov-accounts.admin.'.$key.'.index') }}"><span class="lic-admin-card__icon"><i class="bi {{ $icon }}"></i></span><h2>{{ __('gov_accounts.references.'.$key) }}</h2><p>{{ __('gov_accounts.admin.subtitle') }}</p><strong class="lic-admin-card__count">{{ (int) $count }}</strong></a>
        @endforeach
    </section>
</div>
@endsection
