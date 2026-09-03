@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-doctors-directory-admin.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory-admin.css')) }}" rel="stylesheet">
@endpush

@section('title', __('system_administration.edit_package'))

@section('sidebar_heading', __('system_administration.title'))
@section('sidebar_subheading', __('system_administration.packages_subtitle'))

@section('content')
    <div class="hm-dda hm-dda--package-form">
        @include('doctors-directory-admin.partials.dda-breadcrumb', [
            'items' => [
                ['label' => __('system_administration.dashboard'), 'url' => route('modules.system-admin.dashboard')],
                ['label' => __('system_administration.packages'), 'url' => route('modules.system-admin.packages.index')],
                ['label' => __('system_administration.edit_package'), 'chip' => true],
            ],
        ])

        @include('doctors-directory-admin.partials.dda-page-hero', [
            'title' => __('system_administration.edit_package'),
            'subtitle' => $package->localizedName(),
        ])

        @if (session('success'))
            <div class="hm-alert-success mb-3">{{ session('success') }}</div>
        @endif

        <div class="dda-form-panel">
            @include('system-administration.packages._form', [
                'package' => $package,
                'sectionOptions' => $sectionOptions,
                'isCreate' => false,
            ])
        </div>
    </div>
@endsection
