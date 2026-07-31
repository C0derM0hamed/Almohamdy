@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-doctors-directory-admin.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory-admin.css')) }}" rel="stylesheet">
@endpush

@section('title', __('doctors_directory_admin.add_doctor'))

@section('sidebar_heading', __('doctors_directory_admin.title'))
@section('sidebar_subheading', __('doctors_directory_admin.doctors_create_subtitle'))

@section('content')
    <div class="hm-dda hm-dda--doctor-form">
        @include('doctors-directory-admin.partials.dda-breadcrumb', [
            'items' => [
                ['label' => __('doctors_directory_admin.dashboard'), 'url' => route('modules.doctors-admin.dashboard')],
                ['label' => __('doctors_directory_admin.doctors'), 'url' => route('modules.doctors-admin.doctors.index')],
                ['label' => __('doctors_directory_admin.add_doctor'), 'chip' => true],
            ],
        ])

        @include('doctors-directory-admin.partials.dda-page-hero', [
            'title' => __('doctors_directory_admin.add_doctor'),
            'subtitle' => __('doctors_directory_admin.doctors_create_subtitle'),
        ])

        <div class="dda-form-panel">
            @include('doctors-directory-admin.doctors._form', [
                'specialities' => $specialities,
                'countries' => $countries,
                'selectedSpecialityId' => $selectedSpecialityId,
            ])
        </div>
    </div>
@endsection
