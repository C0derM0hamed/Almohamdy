@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-doctors-directory-admin.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory-admin.css')) }}" rel="stylesheet">
@endpush

@section('title', __('doctors_directory_admin.edit_speciality'))

@section('sidebar_heading', __('doctors_directory_admin.title'))
@section('sidebar_subheading', __('doctors_directory_admin.specialities_subtitle'))

@section('content')
    <div class="hm-dda hm-dda--speciality-form">
        @include('doctors-directory-admin.partials.dda-breadcrumb', [
            'items' => [
                ['label' => __('doctors_directory_admin.dashboard'), 'url' => route('modules.doctors-admin.dashboard')],
                ['label' => __('doctors_directory_admin.specialities'), 'url' => route('modules.doctors-admin.specialities.index')],
                ['label' => __('doctors_directory_admin.edit_speciality'), 'chip' => true],
            ],
        ])

        @include('doctors-directory-admin.partials.dda-page-hero', [
            'title' => __('doctors_directory_admin.edit_speciality'),
            'subtitle' => $speciality->localizedName(),
        ])

        @if (session('success'))
            <div class="hm-alert-success mb-3">{{ session('success') }}</div>
        @endif

        <div class="dda-form-panel">
            @include('doctors-directory-admin.specialities._form', ['speciality' => $speciality, 'clinics' => $clinics])
        </div>
    </div>
@endsection
