@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-complaints-redesign.css') }}?v={{ filemtime(public_path('css/hm-complaints-redesign.css')) }}" rel="stylesheet">
@endpush

@section('title', __('complaints.timeline'))

@section('content')
    <div class="hm-cp hm-cp--detail">
        @include('complaints.partials.cp-breadcrumb', [
            'items' => [
                ['label' => __('complaints.dashboard'), 'url' => route('modules.complaints')],
                ['label' => __('complaints.list'), 'url' => route('modules.complaints.index')],
                ['label' => $complaint->displayNumber(), 'url' => route('modules.complaints.show', $complaint->id)],
                ['label' => __('complaints.timeline'), 'chip' => true],
            ],
        ])

        <header class="cp-detail-head">
            <div class="cp-detail-head__copy">
                <h1>{{ __('complaints.timeline') }}</h1>
                <p class="cp-page-subtitle">{{ __('complaints.timeline_subtitle') }}</p>
                <div class="cp-detail-head__badges">
                    <span class="cp-detail-no">{{ $complaint->displayNumber() }}</span>
                    <span class="cp-detail-status" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                </div>
            </div>
        </header>

        @include('complaints.partials.timeline-horizontal', ['timeline' => $timeline])

        <div class="cp-detail-actions">
            <a href="{{ route('modules.complaints.show', $complaint->id) }}" class="cp-btn cp-btn--outline">
                {{ __('complaints.view_detail') }}
            </a>
            <a href="{{ route('modules.complaints.index') }}" class="cp-btn cp-btn--primary">
                {{ __('complaints.view_list') }}
            </a>
        </div>
    </div>
@endsection
