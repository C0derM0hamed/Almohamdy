@extends('layouts.app')
@section('title', __('settings.title'))
@section('figma_page_header', 'true')
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@section('content')
@php
    $settingsGroups = [
        ['eyebrow' => __('settings.company_eyebrow'), 'title' => __('settings.company'), 'description' => __('settings.company_description'), 'items' => [
            ['label' => __('settings.company_groups'), 'description' => __('settings.company_groups_description'), 'href' => route('modules.system-admin.reference.index', 'companies'), 'icon' => 'item-01.svg'],
            ['label' => __('settings.branches'), 'description' => __('settings.branches_description'), 'href' => route('modules.system-admin.reference.index', 'branches'), 'icon' => 'item-02.svg'],
        ]],
        ['eyebrow' => __('settings.branch_eyebrow'), 'title' => __('settings.branch'), 'description' => __('settings.branch_description'), 'items' => [
            ['label' => __('settings.departments'), 'description' => __('settings.departments_description'), 'href' => route('modules.system-admin.reference.index', 'departments'), 'icon' => 'item-03.svg'],
            ['label' => __('settings.needs'), 'description' => __('settings.needs_description'), 'href' => route('modules.system-admin.reference.index', 'needs'), 'icon' => 'item-04.svg'],
            ['label' => __('settings.service_types'), 'description' => __('settings.service_types_description'), 'href' => route('modules.system-admin.reference.index', 'service-types'), 'icon' => 'item-05.svg'],
        ]],
        ['eyebrow' => __('settings.services_eyebrow'), 'title' => __('settings.services'), 'description' => __('settings.services_description'), 'items' => [
            ['label' => __('settings.packages'), 'description' => __('settings.packages_description'), 'href' => route('modules.system-admin.packages.index'), 'icon' => 'item-06.svg'],
            ['label' => __('settings.governmental_services'), 'description' => __('settings.governmental_services_description'), 'href' => route('modules.system-admin.reference.index', 'governmental-services'), 'icon' => 'item-07.svg'],
        ]],
    ];
@endphp
<div class="hm-fm hm-workflow hm-settings-figma">
    @include('layouts.partials.figma-module-header', [
        'crumbs' => [['label' => __('settings.services_root')], ['label' => __('settings.title')]],
        'title' => __('settings.title'), 'subtitle' => __('settings.subtitle'),
        'heroIconSrc' => asset('images/figma/settings/hero.svg'), 'heroIconSize' => 32,
    ])
    @if($isAdmin)
        <section class="hm-settings-control" aria-labelledby="settings-control-title">
            <div class="hm-settings-control__copy"><span>{{ __('settings.control_eyebrow') }}</span><h1 id="settings-control-title">{{ __('settings.control_title') }}</h1><p>{{ __('settings.control_description') }}</p></div>
            <div class="hm-settings-control__art" aria-hidden="true"><span class="hm-settings-control__orbit"></span><img src="{{ asset('images/figma/settings/organization.svg') }}" alt=""></div>
        </section>
        <div class="hm-settings-groups">
            @foreach($settingsGroups as $group)
                <section class="hm-settings-group">
                    <header class="hm-settings-group__header"><span>{{ $group['eyebrow'] }}</span><h2>{{ $group['title'] }}</h2><p>{{ $group['description'] }}</p></header>
                    <div class="hm-settings-group__items">
                        @foreach($group['items'] as $item)
                            <a class="hm-settings-link" href="{{ $item['href'] }}">
                                <span class="hm-settings-link__number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="hm-settings-link__icon"><img src="{{ asset('images/figma/settings/'.$item['icon']) }}" alt=""></span>
                                <span class="hm-settings-link__copy"><strong>{{ $item['label'] }}</strong><small>{{ $item['description'] }}</small></span>
                                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <div class="hm-settings-empty"><i class="bi bi-shield-lock" aria-hidden="true"></i><p>{{ __('settings.no_access') }}</p></div>
    @endif
</div>
@endsection
