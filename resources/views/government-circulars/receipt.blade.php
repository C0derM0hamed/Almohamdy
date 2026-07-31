@extends('layouts.app')

@section('title', __('government_circulars.receipt.title'))
@section('sidebar_heading', __('government_circulars.title'))
@section('sidebar_subheading', __('government_circulars.receipt.subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-gc">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.government-circulars.index') }}">{{ __('government_circulars.list') }}</a>
            <span>/</span>
            <a href="{{ route('modules.government-circulars.show', $circular->id) }}">{{ $circular->displayNumber() }}</a>
            <span>/</span>
            <span class="is-chip">{{ __('government_circulars.receipt.title') }}</span>
        </nav>

        @include('government-circulars.partials.receipt-body', ['modalMode' => false])
    </div>
@endsection
