@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-work-absence-notification.css') }}?v={{ filemtime(public_path('css/hm-work-absence-notification.css')) }}" rel="stylesheet">
@endpush

@section('title', __('work_absence_notification.service_title'))

@section('sidebar_heading', __('work_absence_notification.title'))
@section('sidebar_subheading', __('work_absence_notification.service_subtitle'))

@section('content')
    @php $isRtl = app()->getLocale() === 'ar'; @endphp

    <div class="hm-hs hm-wan hm-wan--list">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('employee_services.title'), 'url' => route('modules.employee-services')],
                ['label' => __('work_absence_notification.service_title'), 'chip' => true],
            ],
        ])

        <section class="hs-page-hero" aria-labelledby="wanServiceTitle">
            <div>
                <h1 id="wanServiceTitle">{{ __('work_absence_notification.service_title') }}</h1>
                <p>{{ __('work_absence_notification.service_subtitle') }}</p>
            </div>
            <div class="hs-page-hero-art" aria-hidden="true"></div>
        </section>

        <div class="hs-filter-card">
            <div class="hs-filter-head">
                <span class="hs-filter-icon" aria-hidden="true"><i class="bi bi-funnel"></i></span>
                <h2>{{ __('work_absence_notification.filters_title') }}</h2>
            </div>

            <form method="GET" action="{{ route('modules.work-absence.notifications.index') }}" class="hs-filter-grid wan-filter-grid">
                @if ($filters['period'] !== '')
                    <input type="hidden" name="period" value="{{ $filters['period'] }}">
                @endif

                <div class="hs-field">
                    <label for="wanNotificationType">{{ __('work_absence_notification.filters.notification_type') }}</label>
                    <select id="wanNotificationType" name="notification_type">
                        <option value="">{{ __('work_absence_notification.filters.notification_type_all') }}</option>
                        @foreach ($notificationTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) $filters['notification_type'] === (string) $type->id)>
                                {{ $type->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="hs-field">
                    <label for="wanDateFrom">{{ __('work_absence_notification.filters.date_from') }}</label>
                    <input
                        type="date"
                        id="wanDateFrom"
                        name="date_from"
                        value="{{ $filters['date_from'] }}"
                    >
                </div>

                <div class="hs-field">
                    <label for="wanDateTo">{{ __('work_absence_notification.filters.date_to') }}</label>
                    <input
                        type="date"
                        id="wanDateTo"
                        name="date_to"
                        value="{{ $filters['date_to'] }}"
                    >
                </div>

                <button type="submit" class="hs-btn hs-btn--primary">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    {{ __('work_absence_notification.search') }}
                </button>

                @if ($hasFilters)
                    <a href="{{ route('modules.work-absence.notifications.index') }}" class="hs-btn hs-btn--ghost">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        {{ __('work_absence_notification.reset') }}
                    </a>
                @endif

                @can('work_absence_notification.export')
                    @php
                        $exportQuery = request()->except(['page', 'format']);
                    @endphp
                    <div class="dropdown wan-export">
                        <button type="button" class="hs-btn hs-btn--ghost dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download" aria-hidden="true"></i>
                            {{ __('work_absence_notification.export.button') }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('modules.work-absence.notifications.export', array_merge($exportQuery, ['format' => 'csv'])) }}">
                                    {{ __('work_absence_notification.export.csv') }}
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('modules.work-absence.notifications.export', array_merge($exportQuery, ['format' => 'excel'])) }}">
                                    {{ __('work_absence_notification.export.excel') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                @endcan
            </form>
        </div>

        <div class="wan-list-toolbar">
            <div class="wan-list-toolbar__summary">
                {{ __('work_absence_notification.total_records', ['count' => number_format($notifications->total())]) }}
            </div>
        </div>

        @if ($notifications->count() > 0)
            <div class="hs-list-panel">
                <div class="wan-table-wrap">
                    <div class="wan-table-scroll">
                        <table class="wan-table">
                            <thead>
                                <tr>
                                    <th>{{ __('work_absence_notification.columns.model') }}</th>
                                    <th>{{ __('work_absence_notification.columns.documents') }}</th>
                                    <th>{{ __('work_absence_notification.columns.absence_type') }}</th>
                                    <th>{{ __('work_absence_notification.columns.release_date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($notifications as $item)
                                    <tr class="wan-table__row-link" data-href="{{ route('modules.work-absence.notifications.show', $item->id) }}">
                                        <td class="wan-table__model">{{ $item->modelLabel() }}</td>
                                        <td>
                                            @if ($item->hasAttachment())
                                                <a
                                                    href="{{ $item->attachmentUrl() }}"
                                                    class="wan-doc-link"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    onclick="event.stopPropagation();"
                                                >
                                                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                                                    {{ __('work_absence_notification.view') }}
                                                </a>
                                            @else
                                                <span class="wan-table__muted">{{ __('work_absence_notification.no_attachment') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->absenceTypeLabel() }}</td>
                                        <td>{{ $item->formattedReleaseDate() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $notifications->links('pagination.hm') }}
                </div>
            </div>
        @else
            <div class="hs-empty">
                <i class="bi bi-bell-slash" aria-hidden="true"></i>
                <p class="mb-0">{{ $hasFilters ? __('work_absence_notification.no_results') : __('work_absence_notification.no_notifications') }}</p>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.wan-table__row-link[data-href]').forEach(function (row) {
            row.addEventListener('click', function (event) {
                if (event.target.closest('a')) {
                    return;
                }

                window.location.href = row.getAttribute('data-href');
            });
        });
    </script>
@endpush
