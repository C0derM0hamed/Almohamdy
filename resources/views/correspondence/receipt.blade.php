@extends('layouts.app')

@section('title', __('correspondence.receipt.title'))
@section('sidebar_heading', __('correspondence.title'))
@section('sidebar_subheading', __('correspondence.receipt.subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inspection-visits.css') }}?v={{ filemtime(public_path('css/hm-inspection-visits.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-gc hm-iv">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.correspondence.index') }}">{{ __('correspondence.list') }}</a>
            <span>/</span>
            <a href="{{ route('modules.correspondence.show', $item->id) }}">{{ $item->displayNumber() }}</a>
            <span>/</span>
            <span class="is-chip">{{ __('correspondence.receipt.title') }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('correspondence.receipt.title') }}</h1>
                <p>{{ $item->subject() ?: $item->displayNumber() }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center no-print">
                <span class="gc-badge" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                <span class="gc-badge" style="background:#1a4f86;">
                    {{ __('correspondence.receipt.summary', ['viewed' => $viewedCount, 'total' => $reports->count()]) }}
                </span>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                    {{ __('correspondence.receipt.print') }}
                </button>
                <a href="{{ route('modules.correspondence.show', $item->id) }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('correspondence.back_to_detail') }}
                </a>
            </div>
        </div>

        <section class="gc-panel mb-3">
            <div class="gc-detail-grid">
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.authority') }}</span>
                    <span class="gc-detail-item__value">{{ $item->authority?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.sector') }}</span>
                    <span class="gc-detail-item__value">{{ $item->sector?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.received_date') }}</span>
                    <span class="gc-detail-item__value">{{ $item->received_date?->format('Y-m-d H:i') ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.section') }}</span>
                    <span class="gc-detail-item__value">{{ $item->section?->localizedName() ?: '—' }}</span>
                </div>
            </div>
        </section>

        <section class="gc-panel">
            <div class="gc-table-wrap">
                @if ($reports->isEmpty())
                    <div class="gc-empty">{{ __('correspondence.receipt.empty') }}</div>
                @else
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('correspondence.receipt.recipient') }}</th>
                                <th>{{ __('correspondence.receipt.department') }}</th>
                                <th>{{ __('correspondence.receipt.channels') }}</th>
                                <th>{{ __('correspondence.receipt.viewing_status') }}</th>
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
                                            <span class="gc-badge" style="background:#15803d;">{{ __('correspondence.receipt.viewed') }}</span>
                                        @else
                                            <span class="gc-badge" style="background:#b45309;">{{ __('correspondence.receipt.not_viewed') }}</span>
                                        @endif
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
