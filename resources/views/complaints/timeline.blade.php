@extends('layouts.app')

@php
    $displayNumber = $complaint->displayNumber();
@endphp

@section('title', __('complaints.timeline'))
@section('sidebar_heading', __('complaints.title'))
@section('sidebar_subheading', __('complaints.detail_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-complaints-redesign.css') }}?v={{ filemtime(public_path('css/hm-complaints-redesign.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-cp hm-cp--detail">
        @include('complaints.partials.cp-breadcrumb', [
            'items' => [
                ['label' => __('complaints.dashboard'), 'url' => route('modules.complaints')],
                ['label' => $displayNumber, 'url' => route('modules.complaints.show', $complaint->id)],
                ['label' => __('complaints.timeline'), 'chip' => true],
            ],
        ])

        <header class="cp-detail-head">
            <div class="cp-detail-head__copy">
                <h1>{{ __('complaints.timeline') }}</h1>
                <div class="cp-detail-head__badges">
                    <span class="cp-detail-no">{{ $displayNumber }}</span>
                    <span class="cp-detail-status" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                </div>
            </div>
            <div class="cp-detail-head__actions">
                <button type="button" class="cp-btn cp-btn--outline" onclick="window.print()">
                    <i class="bi bi-printer" aria-hidden="true"></i>
                    {{ __('complaints.print') }}
                </button>
            </div>
        </header>

        @include('complaints.partials.timeline-horizontal', ['timeline' => $timeline])

        <div class="cp-detail-actions">
            <a href="{{ route('modules.complaints.show', $complaint->id) }}" class="cp-btn cp-btn--outline">
                <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-arrow-right' : 'bi-arrow-left' }}" aria-hidden="true"></i>
                {{ __('complaints.detail') }}
            </a>
        </div>
    </div>
@endsection
