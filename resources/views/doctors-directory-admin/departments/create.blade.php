@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-doctors-directory-admin.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory-admin.css')) }}" rel="stylesheet">
@endpush

@section('title', __('doctors_directory_admin.add_department_assignment'))

@section('sidebar_heading', __('doctors_directory_admin.title'))
@section('sidebar_subheading', __('doctors_directory_admin.departments_create_subtitle'))

@section('content')
    <div class="hm-dda hm-dda--department-form">
        @include('doctors-directory-admin.partials.dda-breadcrumb', [
            'items' => [
                ['label' => __('doctors_directory_admin.dashboard'), 'url' => route('modules.doctors-admin.dashboard')],
                ['label' => __('doctors_directory_admin.departments'), 'url' => route('modules.doctors-admin.departments.index')],
                ['label' => __('doctors_directory_admin.add_department_assignment'), 'chip' => true],
            ],
        ])

        @include('doctors-directory-admin.partials.dda-page-hero', [
            'title' => __('doctors_directory_admin.add_department_assignment'),
            'subtitle' => __('doctors_directory_admin.departments_create_subtitle'),
        ])

        <div class="dda-form-panel">
            @include('doctors-directory-admin.departments._form', [
                'specialities' => $specialities,
                'departmentOptions' => $departmentOptions,
                'selectedSpecialityId' => $selectedSpecialityId,
            ])
        </div>
    </div>
@endsection
