@extends('layouts.app')

@php
    $displayNumber = $complaint->displayNumber();
@endphp

@section('title', __('complaints.detail'))

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
                ['label' => __('complaints.list'), 'url' => route('modules.complaints')],
                ['label' => $displayNumber, 'chip' => true],
            ],
        ])

        <header class="cp-detail-head">
            <div class="cp-detail-head__copy">
                <h1>{{ __('complaints.detail') }}</h1>
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

        <section class="cp-detail-grid">
            <article class="cp-info-card">
                <div class="cp-info-card__head">
                    <span class="cp-info-card__icon" aria-hidden="true"><i class="bi bi-person"></i></span>
                    <h2>{{ __('complaints.sections.complainant_info') }}</h2>
                </div>
                <div class="cp-info-list">
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.complainant') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->localizedComplainantName() ?: '—' }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.patient') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->localizedPatientName() ?: '—' }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.mobile') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->mobile ?: '—' }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.file_no') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->file_number ?: '—' }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.department') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->department?->localizedName() ?? '—' }}</span>
                    </div>
                </div>
            </article>

            <article class="cp-info-card">
                <div class="cp-info-card__head">
                    <span class="cp-info-card__icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                    <h2>{{ __('complaints.sections.complaint_info') }}</h2>
                </div>
                <div class="cp-info-list">
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.complaint_no') }}</span>
                        <span class="cp-info-row__value">{{ $displayNumber }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.type') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->typeLabel() ?: '—' }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.date') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->formattedComplaintDate() }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.created_at') }}</span>
                        <span class="cp-info-row__value">
                            {{ $complaint->created_at ? \Carbon\Carbon::parse($complaint->created_at)->format('Y-m-d H:i') : '—' }}
                        </span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.defendant') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->defendant ?: '—' }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.details') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->details ?: '—' }}</span>
                    </div>
                </div>
            </article>
        </section>

        <article class="cp-info-card cp-info-card--investigation">
            <div class="cp-info-card__head">
                <span class="cp-info-card__icon" aria-hidden="true"><i class="bi bi-search"></i></span>
                <h2>{{ __('complaints.sections.investigation') }}</h2>
            </div>
            <div class="cp-info-list">
                <div class="cp-info-row">
                    <span class="cp-info-row__label">{{ __('complaints.fields.result') }}</span>
                    <span class="cp-info-row__value">{{ $complaint->result ?: '—' }}</span>
                </div>
                <div class="cp-info-row">
                    <span class="cp-info-row__label">{{ __('complaints.fields.employee_investigation') }}</span>
                    <span class="cp-info-row__value">{{ $complaint->employee_investigation ?: '—' }}</span>
                </div>
                <div class="cp-info-row">
                    <span class="cp-info-row__label">{{ __('complaints.fields.status') }}</span>
                    <span class="cp-info-row__value">
                        <span class="cp-detail-status cp-detail-status--inline" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                    </span>
                </div>
            </div>
        </article>

        @include('complaints.partials.timeline-horizontal', ['timeline' => $timeline])

        <div class="cp-detail-actions">
            <a href="{{ route('modules.complaints') }}" class="cp-btn cp-btn--outline">
                <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-arrow-right' : 'bi-arrow-left' }}" aria-hidden="true"></i>
                {{ __('complaints.back_to_list') }}
            </a>
            <a href="{{ route('modules.complaints') }}" class="cp-btn cp-btn--primary">
                {{ __('complaints.view_list') }}
            </a>
        </div>
    </div>

    @include('complaints.partials.timeline-modal', [
        'complaint' => $complaint,
        'timeline' => $timeline,
        'displayNumber' => $displayNumber,
        'statusLabel' => $statusLabel,
    ])
@endsection

@push('scripts')
    <script src="{{ asset('js/hm-complaint-timeline-modal.js') }}?v={{ filemtime(public_path('js/hm-complaint-timeline-modal.js')) }}" defer></script>
@endpush
