@extends('layouts.app')

@section('title', __('inspection_visits.receipt.title'))
@section('sidebar_heading', __('inspection_visits.title'))
@section('sidebar_subheading', __('inspection_visits.receipt.subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inspection-visits.css') }}?v={{ filemtime(public_path('css/hm-inspection-visits.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-gc hm-iv">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.inspection-visits.index') }}">{{ __('inspection_visits.list') }}</a>
            <span>/</span>
            <a href="{{ route('modules.inspection-visits.show', $visit->id) }}">{{ $visit->displayNumber() }}</a>
            <span>/</span>
            <span class="is-chip">{{ __('inspection_visits.receipt.title') }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('inspection_visits.receipt.title') }}</h1>
                <p>{{ $visit->visitNumberRecord?->subject ?: $visit->displayNumber() }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center no-print">
                <span class="gc-badge" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                <span class="gc-badge" style="background:#1a4f86;">
                    {{ __('inspection_visits.receipt.summary', ['viewed' => $viewedCount, 'total' => $reports->count()]) }}
                </span>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                    {{ __('inspection_visits.receipt.print') }}
                </button>
                <a href="{{ route('modules.inspection-visits.show', $visit->id) }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('inspection_visits.back_to_detail') }}
                </a>
            </div>
        </div>

        <section class="gc-panel mb-3">
            <div class="gc-detail-grid">
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.authority') }}</span>
                    <span class="gc-detail-item__value">{{ $visit->authority?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.visit_type') }}</span>
                    <span class="gc-detail-item__value">{{ $visit->visitType?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.visit_date') }}</span>
                    <span class="gc-detail-item__value">{{ optional($visit->visit_date)->format('Y-m-d H:i') ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.section') }}</span>
                    <span class="gc-detail-item__value">{{ $visit->section?->localizedName() ?: '—' }}</span>
                </div>
            </div>
        </section>

        <section class="gc-panel">
            <div class="gc-table-wrap">
                @if ($reports->isEmpty())
                    <div class="gc-empty">{{ __('inspection_visits.receipt.empty') }}</div>
                @else
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('inspection_visits.receipt.recipient') }}</th>
                                <th>{{ __('inspection_visits.receipt.department') }}</th>
                                <th>{{ __('inspection_visits.receipt.channels') }}</th>
                                <th>{{ __('inspection_visits.receipt.viewing_status') }}</th>
                                <th>{{ __('inspection_visits.receipt.sent_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                @php
                                    $admin = $report->administrator;
                                    $viewed = $report->hasBeenViewed();
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $admin?->displayName() ?: '—' }}</strong>
                                        <div class="text-muted small">{{ $admin?->email ?: '—' }}</div>
                                        <div class="text-muted small" dir="ltr">{{ $admin?->mobile ?: '—' }}</div>
                                    </td>
                                    <td>{{ $admin?->section?->localizedName() ?: '—' }}</td>
                                    <td>
                                        <span class="badge text-bg-light border">Email</span>
                                        <span class="badge text-bg-light border">SMS</span>
                                    </td>
                                    <td>
                                        @if ($viewed)
                                            <span class="gc-badge" style="background:#15803d;">{{ __('inspection_visits.receipt.viewed') }}</span>
                                            <div class="text-muted small mt-1">
                                                @if ($report->seen_by_email_at)
                                                    Email: {{ $report->seen_by_email_at->format('Y-m-d H:i') }}
                                                @endif
                                                @if ($report->seen_by_sms_at)
                                                    <div>SMS: {{ $report->seen_by_sms_at->format('Y-m-d H:i') }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="gc-badge" style="background:#b45309;">{{ __('inspection_visits.receipt.not_viewed') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($report->created_at)->format('Y-m-d H:i') ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>
    </div>
@endsection
