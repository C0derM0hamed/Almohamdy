@extends('layouts.app')

@section('title', __('data_requests.receipt.title'))
@section('sidebar_heading', __('data_requests.title'))
@section('sidebar_subheading', __('data_requests.receipt.subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inspection-visits.css') }}?v={{ filemtime(public_path('css/hm-inspection-visits.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-gc hm-iv">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.data-requests.index') }}">{{ __('data_requests.list') }}</a>
            <span>/</span>
            <a href="{{ route('modules.data-requests.show', $request->id) }}">{{ $request->displayNumber() }}</a>
            <span>/</span>
            <span class="is-chip">{{ __('data_requests.receipt.title') }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('data_requests.receipt.title') }}</h1>
                <p>{{ $request->subject() ?: $request->displayNumber() }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center no-print">
                <span class="gc-badge" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                <span class="gc-badge" style="background:#1a4f86;">
                    {{ __('data_requests.receipt.summary', ['viewed' => $viewedCount, 'total' => $reports->count()]) }}
                </span>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                    {{ __('data_requests.receipt.print') }}
                </button>
                <a href="{{ route('modules.data-requests.show', $request->id) }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('data_requests.back_to_detail') }}
                </a>
            </div>
        </div>

        <section class="gc-panel mb-3">
            <div class="gc-detail-grid">
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.entity') }}</span>
                    <span class="gc-detail-item__value">{{ $request->entity?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.data_type') }}</span>
                    <span class="gc-detail-item__value">{{ $request->dataType?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.request_date') }}</span>
                    <span class="gc-detail-item__value">{{ $request->date ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.section') }}</span>
                    <span class="gc-detail-item__value">{{ $request->section?->localizedName() ?: '—' }}</span>
                </div>
            </div>
        </section>

        <section class="gc-panel">
            <div class="gc-table-wrap">
                @if ($reports->isEmpty())
                    <div class="gc-empty">{{ __('data_requests.receipt.empty') }}</div>
                @else
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('data_requests.receipt.recipient') }}</th>
                                <th>{{ __('data_requests.receipt.department') }}</th>
                                <th>{{ __('data_requests.receipt.channels') }}</th>
                                <th>{{ __('data_requests.receipt.viewing_status') }}</th>
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
                                            <span class="gc-badge" style="background:#15803d;">{{ __('data_requests.receipt.viewed') }}</span>
                                        @else
                                            <span class="gc-badge" style="background:#b45309;">{{ __('data_requests.receipt.not_viewed') }}</span>
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
