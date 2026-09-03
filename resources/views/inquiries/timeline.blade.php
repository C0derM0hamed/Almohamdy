@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-doctors-figma.css') }}?v={{ filemtime(public_path('css/hm-doctors-figma.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inquiries.css') }}?v={{ filemtime(public_path('css/hm-inquiries.css')) }}" rel="stylesheet">
@endpush

@section('figma_page_header', true)

@section('title', __('inquiries.timeline'))

@section('sidebar_heading', __('inquiries.title'))
@section('sidebar_subheading', __('inquiries.subtitle'))

@section('content')
    @php
        $listRoute = $direction === 'incoming'
            ? route('modules.inquiries.incoming.index')
            : route('modules.inquiries.outgoing.index');
    @endphp

    <div class="hm-dd hm-dd--figma hm-inq hm-inq--timeline inq-detail-page">
        <header class="dd-figma-head">
            <div class="dd-figma-head__row">
                <div class="dd-figma-head__page">
                    @include('doctors-directory.partials.dd-breadcrumb', [
                        'variant' => 'plain',
                        'items' => [
                            ['label' => __('dashboard.modules')],
                            ['label' => __('inquiries.title'), 'url' => $listRoute],
                            ['label' => __('inquiries.timeline'), 'chip' => true],
                        ],
                    ])

                    <div class="dd-figma-hero inq-detail-hero">
                        <div class="dd-figma-hero__icon" aria-hidden="true"><i class="bi bi-clock-history"></i></div>
                        <div class="dd-figma-hero__copy">
                            <h1 id="inqTimelineTitle">{{ __('inquiries.timeline') }}</h1>
                            <p>{{ __('inquiries.timeline_subtitle') }}</p>
                        </div>
                        <a href="{{ route('modules.inquiries.pdf', ['direction' => $direction, 'inquiry' => $inquiry->id]) }}" class="inq-detail-hero__action">
                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                            {{ __('inquiries.download_pdf') }}
                        </a>
                    </div>
                </div>
                @include('layouts.partials.figma-header-tools')
            </div>
        </header>

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
