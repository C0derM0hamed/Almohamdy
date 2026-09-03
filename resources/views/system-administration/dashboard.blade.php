@extends('layouts.app')

@section('figma_page_header', true)

@push('styles')
    <link href="{{ asset('css/hm-doctors-figma.css') }}?v={{ filemtime(public_path('css/hm-doctors-figma.css')) }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/hm-services-dashboard-search.js') }}?v={{ filemtime(public_path('js/hm-services-dashboard-search.js')) }}" defer></script>
@endpush

@section('title', __('system_administration.dashboard'))
@section('sidebar_heading', __('system_administration.title'))
@section('sidebar_subheading', __('system_administration.dashboard_subtitle'))

@section('content')
    @include('layouts.partials.figma-dashboard', [
        'pageClass' => 'hm-system-administration-dashboard',
        'breadcrumbs' => [
            ['label' => __('dashboard.modules')],
            ['label' => __('system_administration.dashboard'), 'chip' => true],
        ],
        'title' => __('system_administration.dashboard'),
        'subtitle' => __('system_administration.dashboard_subtitle'),
        'heroIcon' => 'bi-shield-lock',
        'stats' => [
            [
                'label' => __('system_administration.stats.total'),
                'value' => $summary['total'],
                'hint' => __('system_administration.stats.total_hint'),
                'icon' => 'bi-hospital',
                'variant' => 'primary',
                'url' => route('modules.system-admin.packages.index'),
            ],
            [
                'label' => __('system_administration.stats.published'),
                'value' => $summary['published'],
                'hint' => __('system_administration.stats.published_hint'),
                'icon' => 'bi-check-circle',
                'variant' => 'dark',
                'url' => route('modules.system-admin.packages.index', ['publish' => '1']),
            ],
            [
                'label' => __('system_administration.stats.unpublished'),
                'value' => $summary['unpublished'],
                'hint' => __('system_administration.stats.unpublished_hint'),
                'icon' => 'bi-eye-slash',
                'variant' => 'primary',
                'available' => true,
                'url' => route('modules.system-admin.packages.index', ['publish' => '0']),
            ],
        ],
        'filterTitle' => __('system_administration.filters_title'),
        'filterSubtitle' => __('system_administration.dashboard_filter_subtitle'),
        'searchPlaceholder' => __('system_administration.dashboard_search_placeholder'),
        'searchLabel' => __('system_administration.search'),
        'resetLabel' => __('system_administration.reset'),
        'sectionTitle' => __('system_administration.dashboard_section_title'),
        'sectionSubtitle' => __('system_administration.dashboard_section_subtitle'),
        'countLabel' => __('system_administration.dashboard_count_label'),
        'cardActionLabel' => __('system_administration.open_module'),
        'emptyMessage' => __('system_administration.empty_title'),
        'cards' => $cards,
        'actions' => [
            [
                'label' => __('system_administration.manage_packages'),
                'url' => route('modules.system-admin.packages.index'),
                'icon' => 'bi-list-ul',
                'primary' => true,
            ],
            [
                'label' => __('system_administration.manage_doctors_directory'),
                'url' => route('modules.doctors-admin.dashboard'),
                'icon' => 'bi-gear-wide-connected',
            ],
            [
                'label' => __('system_administration.view_public_catalog'),
                'url' => route('modules.hospital-services'),
                'icon' => 'bi-box-arrow-up-right',
                'external' => true,
            ],
        ],
    ])
@endsection
