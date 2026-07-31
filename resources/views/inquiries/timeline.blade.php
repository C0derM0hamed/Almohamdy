@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-inquiries.css') }}?v={{ filemtime(public_path('css/hm-inquiries.css')) }}" rel="stylesheet">
@endpush

@section('title', __('inquiries.timeline'))

@section('sidebar_heading', __('inquiries.title'))
@section('sidebar_subheading', __('inquiries.subtitle'))

@section('content')
    @php
        $listRoute = $direction === 'incoming'
            ? route('modules.inquiries.incoming.index')
            : route('modules.inquiries.outgoing.index');
    @endphp

    <div class="hm-inq hm-inq--timeline">
        @include('inquiries.partials.inq-breadcrumb', [
            'items' => [
                ['label' => __('inquiries.title'), 'url' => $listRoute],
                ['label' => __('inquiries.timeline'), 'chip' => true],
            ],
        ])

        <section class="inq-page-hero inq-page-hero--timeline" aria-labelledby="inqTimelineTitle">
            <div>
                <h1 id="inqTimelineTitle">{{ __('inquiries.timeline') }}</h1>
                <p>{{ __('inquiries.timeline_subtitle') }}</p>
                <div class="inq-page-hero__actions">
                    <a
                        href="{{ route('modules.inquiries.pdf', ['direction' => $direction, 'inquiry' => $inquiry->id]) }}"
                        class="btn hm-btn hm-btn--outline hm-inq-btn"
                    >
                        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                        {{ __('inquiries.download_pdf') }}
                    </a>
                </div>
            </div>
            <div class="inq-page-hero-art" aria-hidden="true"></div>
        </section>

        @include('inquiries.partials.timeline-modal-body', [
            'direction' => $direction,
            'inquiry' => $inquiry,
            'timeline' => $timeline,
            'statusLabel' => $statusLabel,
            'statusColor' => $statusColor,
            'modalMode' => false,
        ])

        <div class="inq-back-row">
            <a href="{{ $listRoute }}" class="inq-back-btn">
                <i class="bi bi-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('inquiries.view_list') }}
            </a>
        </div>
    </div>
@endsection
