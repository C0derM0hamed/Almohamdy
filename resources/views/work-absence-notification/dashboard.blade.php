@extends('layouts.app')

@section('title', __('work_absence_notification.dashboard'))

@section('sidebar_heading', __('work_absence_notification.title'))
@section('sidebar_subheading', __('work_absence_notification.dashboard_subtitle'))

@php
    $isRtl = app()->getLocale() === 'ar';

    $total = (int) ($summary['total'] ?? 0);
    $pending = (int) ($summary['pending'] ?? 0);
    $actionTaken = (int) ($summary['action_taken'] ?? 0);
    $activated = (int) ($summary['activated'] ?? 0);
    $thisMonth = (int) ($summary['this_month'] ?? 0);

    $pct = static fn (int $value): string => $total > 0
        ? number_format(($value / $total) * 100, 1) . '%'
        : '0%';

    $statCards = [
        [
            'key' => 'total',
            'label' => __('work_absence_notification.stats.total'),
            'value' => $total,
            'meta' => __('work_absence_notification.insights.all_time'),
            'icon' => 'bi-bell',
            'tone' => 'primary',
            'url' => route('modules.work-absence.notifications.index'),
        ],
        [
            'key' => 'pending',
            'label' => __('work_absence_notification.stats.pending'),
            'value' => $pending,
            'meta' => __('work_absence_notification.insights.of_total', ['percent' => $pct($pending)]),
            'icon' => 'bi-hourglass-split',
            'tone' => 'teal',
            'url' => route('modules.work-absence.notifications.index', ['status' => 'pending']),
        ],
        [
            'key' => 'action_taken',
            'label' => __('work_absence_notification.stats.action_taken'),
            'value' => $actionTaken,
            'meta' => __('work_absence_notification.insights.of_total', ['percent' => $pct($actionTaken)]),
            'icon' => 'bi-clipboard-check',
            'tone' => 'blue',
            'url' => route('modules.work-absence.notifications.index', ['status' => 'action_taken']),
        ],
        [
            'key' => 'activated',
            'label' => __('work_absence_notification.stats.activated'),
            'value' => $activated,
            'meta' => __('work_absence_notification.insights.of_total', ['percent' => $pct($activated)]),
            'icon' => 'bi-check-circle',
            'tone' => 'green',
            'url' => route('modules.work-absence.notifications.index', ['status' => 'activated']),
        ],
        [
            'key' => 'this_month',
            'label' => __('work_absence_notification.stats.this_month'),
            'value' => $thisMonth,
            'meta' => __('work_absence_notification.insights.of_total', ['percent' => $pct($thisMonth)]),
            'icon' => 'bi-calendar-month',
            'tone' => 'indigo',
            'url' => route('modules.work-absence.notifications.index', ['period' => 'this_month']),
        ],
    ];

    $recipientCards = [
        [
            'label' => __('work_absence_notification.stats.recipients_total'),
            'value' => (int) ($summary['recipients_total'] ?? 0),
            'meta' => __('work_absence_notification.insights.all_time'),
            'icon' => 'bi-people',
            'tone' => 'primary',
        ],
        [
            'label' => __('work_absence_notification.stats.recipients_viewed'),
            'value' => (int) ($summary['recipients_viewed'] ?? 0),
            'meta' => (($summary['recipients_total'] ?? 0) > 0)
                ? number_format((($summary['recipients_viewed'] ?? 0) / $summary['recipients_total']) * 100, 1) . '%'
                : '0%',
            'icon' => 'bi-eye',
            'tone' => 'green',
        ],
        [
            'label' => __('work_absence_notification.stats.recipients_pending_view'),
            'value' => (int) ($summary['recipients_pending_view'] ?? 0),
            'meta' => (($summary['recipients_total'] ?? 0) > 0)
                ? number_format((($summary['recipients_pending_view'] ?? 0) / $summary['recipients_total']) * 100, 1) . '%'
                : '0%',
            'icon' => 'bi-eye-slash',
            'tone' => 'teal',
        ],
    ];

    $hasTrendChart = (bool) ($charts['trend']['has_data'] ?? false);
    $hasTypeChart = (bool) ($charts['type_distribution']['has_data'] ?? false);
    $hasWorkflowChart = (bool) ($charts['workflow_distribution']['has_data'] ?? false);
    $hasRecipientData = (int) ($summary['recipients_total'] ?? 0) > 0;
    $hasMidGrid = $hasRecipientData || $hasTrendChart;
    $hasDistributionCharts = $hasTypeChart || $hasWorkflowChart;

    $reportPanels = array_values(array_filter([
        [
            'title' => __('work_absence_notification.reports.pending_by_type'),
            'labelColumn' => __('work_absence_notification.reports.notification_type'),
            'rows' => $reports['pending_by_type'],
            'variant' => 'pending',
        ],
        [
            'title' => __('work_absence_notification.reports.action_taken_by_type'),
            'labelColumn' => __('work_absence_notification.reports.notification_type'),
            'rows' => $reports['action_taken_by_type'],
            'variant' => 'action_taken',
        ],
        [
            'title' => __('work_absence_notification.reports.activated_by_type'),
            'labelColumn' => __('work_absence_notification.reports.notification_type'),
            'rows' => $reports['activated_by_type'],
            'variant' => 'activated',
        ],
        [
            'title' => __('work_absence_notification.reports.top_absence_reasons'),
            'labelColumn' => __('work_absence_notification.reports.absence_reason'),
            'rows' => $reports['top_absence_reasons'],
            'variant' => 'reasons',
        ],
    ], static fn (array $panel): bool => count($panel['rows']) > 0));

    $hasCharts = $hasTrendChart || $hasTypeChart || $hasWorkflowChart;
@endphp

@push('styles')
    <link href="{{ asset('css/hm-work-absence-notification.css') }}?v={{ filemtime(public_path('css/hm-work-absence-notification.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-wan {{ $isRtl ? 'hm-wan--rtl' : '' }}">
        <nav aria-label="{{ __('breadcrumbs.aria_label') }}" class="wan-breadcrumb wan-breadcrumb--bar">
            <span class="wan-chip">{{ __('work_absence_notification.dashboard') }}</span>
        </nav>

        <header class="wan-head">
            <h1>{{ __('work_absence_notification.dashboard') }}</h1>
            <p>{{ __('work_absence_notification.dashboard_subtitle') }}</p>
        </header>

        <section class="wan-stat-grid" aria-label="{{ __('work_absence_notification.dashboard') }}">
            @foreach ($statCards as $card)
                <a href="{{ $card['url'] }}" class="wan-stat wan-stat--{{ $card['tone'] }}">
                    <div class="wan-stat__top">
                        <div class="wan-stat__copy">
                            <p class="wan-stat__label">{{ $card['label'] }}</p>
                            <p class="wan-stat__value">{{ number_format($card['value']) }}</p>
                            <p class="wan-stat__meta">{{ $card['meta'] }}</p>
                        </div>
                        <span class="wan-stat__icon" aria-hidden="true"><i class="bi {{ $card['icon'] }}"></i></span>
                    </div>
                </a>
            @endforeach
        </section>

        @if ($hasMidGrid)
            <section class="wan-mid-grid {{ ! $hasRecipientData || ! $hasTrendChart ? 'wan-mid-grid--single' : '' }}">
                @if ($hasRecipientData)
                    <article class="wan-panel wan-panel--recipients">
                        <div class="wan-panel__head">
                            <h2>{{ __('work_absence_notification.recipients.dashboard_section') }}</h2>
                        </div>
                        <div class="wan-recipient-grid">
                            @foreach ($recipientCards as $card)
                                <div class="wan-mini wan-mini--{{ $card['tone'] }}">
                                    <span class="wan-mini__icon" aria-hidden="true"><i class="bi {{ $card['icon'] }}"></i></span>
                                    <div class="wan-mini__copy">
                                        <p class="wan-mini__label">{{ $card['label'] }}</p>
                                        <p class="wan-mini__value">{{ number_format($card['value']) }}</p>
                                        <p class="wan-mini__meta">{{ $card['meta'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endif

                @if ($hasTrendChart)
                    <article class="wan-panel wan-panel--trend">
                        <div class="wan-panel__head">
                            <h2>{{ __('work_absence_notification.charts.last_30_days') }}</h2>
                        </div>
                        <div class="wan-chart-wrap wan-chart-wrap--trend">
                            <canvas id="hmWanTrendChart" role="img" aria-label="{{ __('work_absence_notification.charts.last_30_days') }}"></canvas>
                        </div>
                    </article>
                @endif
            </section>
        @endif

        @if ($hasDistributionCharts)
            <section class="wan-chart-grid {{ ! $hasTypeChart || ! $hasWorkflowChart ? 'wan-chart-grid--single' : '' }}">
                @if ($hasTypeChart)
                    <article class="wan-panel">
                        <div class="wan-panel__head">
                            <h2>{{ __('work_absence_notification.charts.type_distribution') }}</h2>
                        </div>
                        <div class="wan-donut-layout">
                            <div class="wan-chart-wrap wan-chart-wrap--donut">
                                <canvas id="hmWanTypeChart" role="img" aria-label="{{ __('work_absence_notification.charts.type_distribution') }}"></canvas>
                                <div class="wan-donut-center">
                                    <span class="wan-donut-center__value">{{ number_format(array_sum($charts['type_distribution']['values'])) }}</span>
                                    <span class="wan-donut-center__label">{{ __('work_absence_notification.insights.total') }}</span>
                                </div>
                            </div>
                            <ul class="wan-legend" id="hmWanTypeLegend"></ul>
                        </div>
                    </article>
                @endif

                @if ($hasWorkflowChart)
                    <article class="wan-panel">
                        <div class="wan-panel__head">
                            <h2>{{ __('work_absence_notification.charts.workflow_distribution') }}</h2>
                        </div>
                        <div class="wan-donut-layout">
                            <div class="wan-chart-wrap wan-chart-wrap--pie">
                                <canvas id="hmWanWorkflowChart" role="img" aria-label="{{ __('work_absence_notification.charts.workflow_distribution') }}"></canvas>
                            </div>
                            <ul class="wan-legend" id="hmWanWorkflowLegend"></ul>
                        </div>
                    </article>
                @endif
            </section>
        @endif

        @if (count($reportPanels) > 0)
            @foreach (array_chunk($reportPanels, 2) as $reportRow)
                <section class="wan-chart-grid {{ count($reportRow) === 1 ? 'wan-chart-grid--single' : '' }}">
                    @foreach ($reportRow as $panel)
                        @include('work-absence-notification.partials.report-breakdown', $panel)
                    @endforeach
                </section>
            @endforeach
        @endif
    </div>

    @if ($hasCharts)
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" crossorigin="anonymous"></script>
            <script>
                window.hmWanCharts = @json($charts);
                window.hmWanChartLabels = {
                    count: @json(__('work_absence_notification.charts.count')),
                    notifications: @json(__('work_absence_notification.charts.notifications')),
                };
                window.hmWanChartLocale = @json(app()->getLocale());
            </script>
            <script src="{{ asset('js/hm-wan-dashboard-charts.js') }}?v={{ filemtime(public_path('js/hm-wan-dashboard-charts.js')) }}"></script>
        @endpush
    @endif
@endsection
