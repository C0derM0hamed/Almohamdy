@extends('layouts.app')

@section('figma_page_header', true)

@push('styles')
    <link href="{{ asset('css/hm-doctors-figma.css') }}?v={{ filemtime(public_path('css/hm-doctors-figma.css')) }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/hm-services-dashboard-search.js') }}?v={{ filemtime(public_path('js/hm-services-dashboard-search.js')) }}" defer></script>
@endpush

@section('title', __('doctors_directory_admin.dashboard'))
@section('sidebar_heading', __('doctors_directory_admin.title'))
@section('sidebar_subheading', __('doctors_directory_admin.dashboard_subtitle'))

@section('content')
    @include('layouts.partials.figma-dashboard', [
        'pageClass' => 'hm-doctors-directory-admin-dashboard',
        'breadcrumbs' => [
            ['label' => __('dashboard.modules')],
            ['label' => __('doctors_directory_admin.dashboard'), 'chip' => true],
        ],
        'title' => __('doctors_directory_admin.dashboard'),
        'subtitle' => __('doctors_directory_admin.dashboard_subtitle'),
        'heroIcon' => 'bi-person-vcard',
        'stats' => [
            [
                'label' => __('doctors_directory_admin.stats.total'),
                'value' => $summary['total'],
                'hint' => __('doctors_directory_admin.stats.total_hint'),
                'icon' => 'bi-diagram-3',
                'variant' => 'primary',
                'url' => route('modules.doctors-admin.specialities.index'),
            ],
            [
                'label' => __('doctors_directory_admin.stats.published'),
                'value' => $summary['published'],
                'hint' => __('doctors_directory_admin.stats.published_hint'),
                'icon' => 'bi-check-circle',
                'variant' => 'dark',
                'url' => route('modules.doctors-admin.specialities.index', ['publish' => '1']),
            ],
            [
                'label' => __('doctors_directory_admin.stats.unpublished'),
                'value' => $summary['unpublished'],
                'hint' => __('doctors_directory_admin.stats.unpublished_hint'),
                'icon' => 'bi-eye-slash',
                'variant' => 'primary',
                'available' => true,
                'url' => route('modules.doctors-admin.specialities.index', ['publish' => '0']),
            ],
        ],
        'filterTitle' => __('doctors_directory_admin.filters_title'),
        'filterSubtitle' => __('doctors_directory_admin.dashboard_filter_subtitle'),
        'searchPlaceholder' => __('doctors_directory_admin.dashboard_search_placeholder'),
        'searchLabel' => __('doctors_directory_admin.search'),
        'resetLabel' => __('doctors_directory_admin.reset'),
        'sectionTitle' => __('doctors_directory_admin.dashboard_section_title'),
        'sectionSubtitle' => __('doctors_directory_admin.dashboard_section_subtitle'),
        'countLabel' => __('doctors_directory_admin.dashboard_count_label'),
        'cardActionLabel' => __('doctors_directory_admin.open_module'),
        'emptyMessage' => __('doctors_directory_admin.empty_title'),
        'cards' => $cards,
        'actions' => [
            [
                'label' => __('doctors_directory_admin.manage_specialities'),
                'url' => route('modules.doctors-admin.specialities.index'),
                'icon' => 'bi-list-ul',
                'primary' => true,
            ],
            [
                'label' => __('doctors_directory_admin.view_public_directory'),
                'url' => route('modules.doctors.specialities.index'),
                'icon' => 'bi-box-arrow-up-right',
                'external' => true,
            ],
        ],
    ])
@endsection
