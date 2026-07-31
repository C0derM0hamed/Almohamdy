@extends('layouts.app')

@section('title', __('government_circulars.departments.title'))
@section('sidebar_heading', __('government_circulars.title'))
@section('sidebar_subheading', __('government_circulars.departments.subtitle'))

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
            <span class="is-chip">{{ __('government_circulars.departments.title') }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('government_circulars.departments.title') }}</h1>
                <p>{{ $circular->subject }}</p>
            </div>
            <a href="{{ route('modules.government-circulars.receipt', $circular->id) }}" class="btn btn-outline-primary btn-sm">
                {{ __('government_circulars.actions.receipt') }}
            </a>
        </div>

        <section class="gc-panel">
            <div class="gc-table-wrap">
                @if ($departments->isEmpty())
                    <div class="gc-empty">{{ __('government_circulars.departments.empty') }}</div>
                @else
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('government_circulars.departments.department') }}</th>
                                <th>{{ __('government_circulars.departments.recipients') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($departments as $department)
                                <tr>
                                    <td>{{ $department->section_name }}</td>
                                    <td>
                                        <a href="{{ route('modules.government-circulars.receipt', $circular->id) }}" class="gc-link-count">
                                            {{ (int) $department->recipients_count }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>
    </div>
@endsection
