@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-doctors-directory-admin.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory-admin.css')) }}" rel="stylesheet">
@endpush

@section('title', __('doctors_directory_admin.edit_doctor'))

@section('sidebar_heading', __('doctors_directory_admin.title'))
@section('sidebar_subheading', __('doctors_directory_admin.doctors_subtitle'))

@section('content')
    <div class="hm-dda hm-dda--doctor-form">
        @include('doctors-directory-admin.partials.dda-breadcrumb', [
            'items' => [
                ['label' => __('doctors_directory_admin.dashboard'), 'url' => route('modules.doctors-admin.dashboard')],
                ['label' => __('doctors_directory_admin.doctors'), 'url' => route('modules.doctors-admin.doctors.index')],
                ['label' => __('doctors_directory_admin.edit_doctor'), 'chip' => true],
            ],
        ])

        @include('doctors-directory-admin.partials.dda-page-hero', [
            'title' => __('doctors_directory_admin.edit_doctor'),
            'subtitle' => $doctor->localizedName(),
        ])

        @if (session('success'))
            <div class="hm-alert-success dda-form-flash">{{ session('success') }}</div>
        @endif

        <div class="dda-form-panel">
            @include('doctors-directory-admin.doctors._form', [
                'doctor' => $doctor,
                'specialities' => $specialities,
                'countries' => $countries,
                'previewUrl' => $previewUrl,
            ])
        </div>

        <div class="dda-form-panel dda-form-panel--assignments">
            @include('doctors-directory-admin.doctors._assignments', [
                'doctor' => $doctor,
                'departments' => $departments,
            ])
        </div>
    </div>
@endsection
