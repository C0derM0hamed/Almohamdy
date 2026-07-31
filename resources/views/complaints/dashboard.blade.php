@extends('layouts.app')

@section('title', __('complaints.title'))

@section('sidebar_heading', __('complaints.title'))
@section('sidebar_subheading', __('complaints.dashboard_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-complaints-redesign.css') }}?v={{ filemtime(public_path('css/hm-complaints-redesign.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-hs hm-cp">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('complaints.title'), 'chip' => true],
            ],
        ])

        <section class="hs-dash-hero" aria-labelledby="complaintsDashboardTitle">
            <div>
                <h1 id="complaintsDashboardTitle">{{ __('complaints.dashboard') }}</h1>
                <p>{{ __('complaints.dashboard_subtitle') }}</p>
            </div>
            <div class="hs-dash-hero-art" aria-hidden="true"></div>
        </section>

        <section class="cp-insights" aria-label="{{ __('complaints.insights.aria_label') }}">
            <article class="cp-insight-card">
                <div>
                    <p class="cp-insight-card__label">{{ __('complaints.insights.processing_rate') }}</p>
                    <p class="cp-insight-card__value">{{ $insights['processing_rate'] }}%</p>
                </div>
                <div class="cp-ring" style="--cp-progress: {{ $insights['processing_rate'] }}" aria-hidden="true">
                    <span>{{ $insights['processing_rate'] }}%</span>
                </div>
            </article>

            <article class="cp-insight-card">
                <div>
                    <p class="cp-insight-card__label">{{ __('complaints.insights.most_active_department') }}</p>
                    <p class="cp-insight-card__value">{{ $insights['most_active_department'] }}</p>
                </div>
                <span class="cp-insight-card__icon" aria-hidden="true">
                    <i class="bi bi-graph-up-arrow"></i>
                </span>
            </article>

            <article class="cp-insight-card">
                <div>
                    <p class="cp-insight-card__label">{{ __('complaints.insights.latest_update') }}</p>
                    <p class="cp-insight-card__value">{{ $insights['latest_update_label'] }}</p>
                </div>
                <span class="cp-insight-card__icon" aria-hidden="true">
                    <i class="bi bi-clock-history"></i>
                </span>
            </article>
        </section>

        <div class="hs-filter-card">
            <div class="hs-filter-head">
                <span class="hs-filter-icon" aria-hidden="true"><i class="bi bi-funnel"></i></span>
                <h2>{{ __('complaints.filters_title') }}</h2>
            </div>

            <form method="GET" action="{{ route('modules.complaints') }}" class="hs-filter-grid cp-filter-grid">
                <div class="hs-field">
                    <label for="complaintSearch">{{ __('complaints.filters.search') }}</label>
                    <div class="hs-input-wrap">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input
                            type="search"
                            id="complaintSearch"
                            name="search"
                            value="{{ $filters['search'] }}"
                            placeholder="{{ __('complaints.filters.search') }}"
                            maxlength="100"
                        >
                    </div>
                </div>

                <div class="hs-field">
                    <label for="complaintStatus">{{ __('complaints.filters.status') }}</label>
                    <select id="complaintStatus" name="status">
                        <option value="">{{ __('complaints.filters.status') }}</option>
                        <option value="0" @selected((string) $filters['status'] === '0')>
                            {{ __('complaints.status.new') }}
                        </option>
                        @foreach ($statusOptions as $statusOption)
                            <option value="{{ $statusOption->id }}" @selected((string) $filters['status'] === (string) $statusOption->id)>
                                {{ $statusOption->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="hs-btn hs-btn--primary">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    {{ __('complaints.search') }}
                </button>

                @if ($hasFilters)
                    <a href="{{ route('modules.complaints') }}" class="hs-btn hs-btn--ghost">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        {{ __('complaints.reset') }}
                    </a>
                @endif
            </form>
        </div>

        @if ($complaints->count() > 0)
            <div class="hs-list-panel">
                <div class="cp-table-wrap">
                    <table class="cp-table">
                        <thead>
                            <tr>
                                <th>{{ __('complaints.columns.complaint_no') }}</th>
                                <th>{{ __('complaints.columns.file_no') }}</th>
                                <th>{{ __('complaints.columns.complainant') }}</th>
                                <th>{{ __('complaints.columns.department') }}</th>
                                <th>{{ __('complaints.columns.date') }}</th>
                                <th>{{ __('complaints.columns.status') }}</th>
                                <th>{{ __('complaints.columns.priority') }}</th>
                                <th>{{ __('complaints.columns.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($complaints as $complaint)
                                @php
                                    $statusLabel = (int) $complaint->status === 0
                                        ? __('complaints.status.new')
                                        : ($complaint->currentStatus?->localizedName() ?? '—');
                                    $statusColor = (int) $complaint->status === 0
                                        ? '#fce7f3'
                                        : ($complaint->currentStatus?->badgeColor() ?? '#e2e8f0');
                                    $priorityLabel = $complaint->priorityLabel();
                                    $priorityClass = (int) $complaint->priority === 1 ? 'is-high' : 'is-low';
                                @endphp
                                <tr>
                                    <td>
                                        <span class="cp-complaint-no" style="background-color: {{ $complaint->numberBadgeColor() }};">
                                            {{ $complaint->displayNumber() }}
                                        </span>
                                    </td>
                                    <td>{{ $complaint->file_number ?: '—' }}</td>
                                    <td>{{ $complaint->localizedComplainantName() ?: '—' }}</td>
                                    <td>{{ $complaint->department?->localizedName() ?? '—' }}</td>
                                    <td>{{ $complaint->formattedComplaintDate() }}</td>
                                    <td>
                                        <span class="cp-status" style="background-color: {{ $statusColor }};">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="cp-priority {{ $priorityClass }}">{{ $priorityLabel }}</span>
                                    </td>
                                    <td>
                                        <div class="cp-actions">
                                            <a href="{{ route('modules.complaints.show', $complaint->id) }}" class="cp-action-btn">
                                                {{ __('complaints.view_detail') }}
                                            </a>
                                            <a href="{{ route('modules.complaints.show', ['complaint' => $complaint->id, 'timeline' => 1]) }}" class="cp-action-btn">
                                                {{ __('complaints.view_timeline') }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $complaints->links('pagination.hm') }}
                </div>
            </div>
        @else
            <div class="hs-empty">
                <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                <p class="mb-0">{{ $hasFilters ? __('complaints.no_results') : __('complaints.no_complaints') }}</p>
            </div>
        @endif
    </div>
@endsection
