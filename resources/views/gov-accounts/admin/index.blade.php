@extends('layouts.app')

@section('title', __('gov_accounts.admin.title'))
@section('sidebar_heading', __('gov_accounts.title'))
@section('sidebar_subheading', __('gov_accounts.admin.subtitle'))

@push('styles')
<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">
<link href="{{ asset('css/hm-gov-accounts-admin.css') }}?v={{ filemtime(public_path('css/hm-gov-accounts-admin.css')) }}" rel="stylesheet">
@endpush

@section('content')
<div class="hm-licenses hm-gov-admin">
    @include('licenses.partials.page-header', ['title' => __('gov_accounts.admin.title'), 'subtitle' => __('gov_accounts.admin.subtitle'), 'icon' => 'bi-sliders'])

    <section class="gov-admin-intro" aria-labelledby="gov-admin-intro-title">
        <div class="gov-admin-intro__icon"><i class="bi bi-shield-check"></i></div>
        <div>
            <h2 id="gov-admin-intro-title">{{ __('gov_accounts.admin.overview_title') }}</h2>
            <p>{{ __('gov_accounts.admin.overview_text') }}</p>
        </div>
    </section>

    <section class="lic-admin-grid gov-admin-cards" aria-label="{{ __('gov_accounts.admin.title') }}">
        @foreach([
            ['key' => 'authorities', 'icon' => 'bi-bank', 'tone' => 'blue'],
            ['key' => 'services', 'icon' => 'bi-grid-1x2', 'tone' => 'teal'],
            ['key' => 'roles', 'icon' => 'bi-person-badge', 'tone' => 'violet'],
            ['key' => 'department-heads', 'icon' => 'bi-diagram-3', 'tone' => 'amber'],
        ] as $card)
            <a class="lic-admin-card gov-admin-card gov-admin-card--{{ $card['tone'] }}" href="{{ route('modules.gov-accounts.admin.'.$card['key'].'.index') }}">
                <span class="lic-admin-card__icon"><i class="bi {{ $card['icon'] }}"></i></span>
                <span class="gov-admin-card__eyebrow">{{ __('gov_accounts.admin.manage_label') }}</span>
                <h2>{{ __('gov_accounts.references.'.$card['key']) }}</h2>
                <p>{{ __('gov_accounts.admin.cards.'.$card['key']) }}</p>
                <span class="gov-admin-card__footer"><strong>{{ (int) ($counts[$card['key']] ?? 0) }}</strong><span>{{ __('gov_accounts.admin.records') }}</span><i class="bi bi-arrow-up-left"></i></span>
            </a>
        @endforeach
    </section>
</div>
@endsection
