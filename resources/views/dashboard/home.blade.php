@extends('layouts.app')

@section('title', __('dashboard.title'))

@push('styles')
    <link href="{{ asset('css/hm-dashboard-figma.css') }}?v={{ filemtime(public_path('css/hm-dashboard-figma.css')) }}" rel="stylesheet">
@endpush

@section('content')
    @php
        $trend = $analytics['trend'];
        $moduleTotals = $analytics['moduleTotals'];
        $statusByModule = $analytics['statusByModule'];
        $branchComparison = $analytics['branchComparison'];
        $doughnutModules = array_values(array_filter(
            array_map(fn ($m) => ['label' => $m['label'], 'total' => $m['total']], $moduleTotals),
            fn ($m) => $m['total'] > 0,
        ));
        $attentionIcons = [
            'inspection_overdue' => 'bi-clipboard2-x',
            'leave_pending' => 'bi-calendar2-check',
            'work_absence_unprocessed' => 'bi-bell-slash',
            'complaints_escalated' => 'bi-exclamation-triangle',
            'correspondence_escalated' => 'bi-envelope-exclamation',
            'appointments_reschedule' => 'bi-calendar2-week',
            'inquiries_new' => 'bi-question-circle',
            'technical_failures_open' => 'bi-wrench-adjustable',
        ];
        $moduleTones = [
            'complaints' => 'red',
            'technical_failures' => 'red',
            'correspondence' => 'indigo',
            'outgoing_letters' => 'indigo',
            'government_circulars' => 'indigo',
            'inquiries' => 'green',
            'work_absence' => 'amber',
            'employee_leave' => 'amber',
        ];
    @endphp

    <div class="hm-figma-dash" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        {{-- Welcome header --}}
        <header class="hm-fd-welcome">
            <p class="hm-fd-welcome__hello">{{ __('dashboard.hello') }}</p>
            <h2 class="hm-fd-welcome__name">{{ $userName }}</h2>
            <p class="hm-fd-welcome__subtitle">{{ __('dashboard.analytics.subtitle') }}</p>
        </header>

        {{-- KPI tiles --}}
        <div class="row g-3 hm-fd-section">
            @foreach ($analytics['kpis'] as $kpi)
                <div class="col-sm-6 col-xl-3">
                    <div class="hm-fd-kpi hm-fd-kpi--{{ $kpi['variant'] }}">
                        <div class="hm-fd-kpi__head">
                            <span class="hm-fd-kpi__title">{{ __('dashboard.analytics.kpis.'.$kpi['key']) }}</span>
                            <span class="hm-fd-kpi__icon"><i class="bi {{ $kpi['icon'] }}" aria-hidden="true"></i></span>
                        </div>
                        <div class="hm-fd-kpi__value">{{ number_format($kpi['value']) }}</div>
                        <div class="hm-fd-kpi__foot">
                            <span>{{ __('dashboard.analytics.since_month') }}</span>
                            <strong>{{ $kpi['growth'] }}%</strong>
                            @if (($kpi['growth'] ?? 0) > 0)
                                <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
                            @else
                                <i class="bi bi-dash-lg" aria-hidden="true"></i>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Trend + records per module --}}
        <div class="row g-3 hm-fd-section">
            <div class="col-xl-8">
                <div class="hm-fd-card h-100">
                    <div class="hm-fd-card__body">
                        <div class="hm-fd-trend-controls">
                            <span class="hm-fd-chip"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ __('dashboard.analytics.periods.daily') }}</span>
                            <div class="hm-fd-period-group" role="group" aria-label="{{ __('dashboard.analytics.charts.trend') }}">
                                <button type="button" class="hm-fd-period is-active" data-period="30">{{ __('dashboard.analytics.periods.d30') }}</button>
                                <button type="button" class="hm-fd-period" data-period="7">{{ __('dashboard.analytics.periods.d7') }}</button>
                                <button type="button" class="hm-fd-period" data-period="90">{{ __('dashboard.analytics.periods.d90') }}</button>
                            </div>
                        </div>
                        <div class="hm-fd-chart hm-fd-chart--trend">
                            <canvas id="hmDashTrend" aria-label="{{ __('dashboard.analytics.charts.trend') }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="hm-fd-card h-100">
                    <div class="hm-fd-card__body d-flex flex-column">
                        <h5 class="hm-fd-card__title">{{ __('dashboard.analytics.charts.modules') }}</h5>
                        @if ($doughnutModules !== [])
                            <div class="hm-fd-chart hm-fd-chart--donut">
                                <canvas id="hmDashModules" aria-label="{{ __('dashboard.analytics.charts.modules') }}"></canvas>
                            </div>
                            <div id="hmFdDonutLegend" class="hm-fd-donut-legend" aria-hidden="true"></div>
                            <div class="hm-fd-donut-total">
                                <span>{{ __('dashboard.analytics.total_label') }}</span>
                                <strong>{{ number_format(array_sum(array_column($doughnutModules, 'total'))) }}</strong>
                            </div>
                        @else
                            <p class="hm-fd-empty">{{ __('dashboard.analytics.empty') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Pending vs completed + branch comparison --}}
        <div class="row g-3 hm-fd-section">
            <div class="{{ $branchComparison !== null ? 'col-xl-6' : 'col-12' }}">
                <div class="hm-fd-card h-100">
                    <div class="hm-fd-card__body">
                        <h5 class="hm-fd-card__title text-center">{{ __('dashboard.analytics.charts.status') }}</h5>
                        @if ($statusByModule !== [])
                            <div class="hm-fd-chart hm-fd-chart--bars">
                                <canvas id="hmDashStatus" aria-label="{{ __('dashboard.analytics.charts.status') }}"></canvas>
                            </div>
                        @else
                            <p class="hm-fd-empty">{{ __('dashboard.analytics.empty') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @if ($branchComparison !== null)
                <div class="col-xl-6">
                    <div class="hm-fd-card h-100">
                        <div class="hm-fd-card__body">
                            <h5 class="hm-fd-card__title text-center">{{ __('dashboard.analytics.charts.branches') }}</h5>
                            <div class="hm-fd-chart hm-fd-chart--bars">
                                <canvas id="hmDashBranches" aria-label="{{ __('dashboard.analytics.charts.branches') }}"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Needs attention + latest activity --}}
        <div class="row g-3 hm-fd-section">
            <div class="col-xl-6">
                <div class="hm-fd-card h-100">
                    <div class="hm-fd-card__body">
                        <div class="hm-fd-list-head">
                            <div>
                                <h5 class="hm-fd-card__title mb-1">{{ __('dashboard.analytics.attention_title') }}</h5>
                                <p class="hm-fd-card__subtitle">{{ __('dashboard.analytics.attention_subtitle') }}</p>
                            </div>
                        </div>
                        <div class="hm-fd-list">
                            @forelse ($analytics['attention'] as $item)
                                <a href="{{ $item['url'] ?? '#' }}" class="hm-fd-attention">
                                    <span class="hm-fd-attention__lead">
                                        <i class="bi {{ $attentionIcons[$item['key']] ?? 'bi-exclamation-circle' }}" aria-hidden="true"></i>
                                        <span class="hm-fd-attention__count">{{ number_format($item['count']) }}</span>
                                    </span>
                                    <span class="hm-fd-attention__label">{{ $item['label'] }}</span>
                                    <span class="hm-fd-attention__chevron"><i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" aria-hidden="true"></i></span>
                                </a>
                            @empty
                                <p class="hm-fd-empty">{{ __('dashboard.analytics.attention_empty') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="hm-fd-card h-100">
                    <div class="hm-fd-card__body">
                        <div class="hm-fd-list-head">
                            <div>
                                <h5 class="hm-fd-card__title mb-1">{{ __('dashboard.analytics.latest_title') }}</h5>
                                <p class="hm-fd-card__subtitle">{{ __('dashboard.analytics.latest_subtitle') }}</p>
                            </div>
                        </div>
                        <div class="hm-fd-list">
                            @forelse ($analytics['latest'] as $item)
                                @php $tone = $moduleTones[$item['module']] ?? 'slate'; @endphp
                                <a href="{{ $item['url'] ?? '#' }}" class="hm-fd-latest">
                                    <span class="hm-fd-avatar hm-fd-tone--{{ $tone }}">{{ mb_substr(trim($item['title']) !== '' ? $item['title'] : '؟', 0, 1) }}</span>
                                    <span class="hm-fd-latest__main">
                                        <span class="hm-fd-latest__title">{{ $item['title'] }}</span>
                                        <span class="hm-fd-badge hm-fd-tone--{{ $tone }}">{{ $item['label'] }}</span>
                                    </span>
                                    @if ($item['date'])
                                        <span class="hm-fd-latest__date">
                                            <i class="bi bi-clock" aria-hidden="true"></i>
                                            <span dir="ltr">{{ $item['date'] }}</span>
                                        </span>
                                    @endif
                                    <span class="hm-fd-attention__chevron"><i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" aria-hidden="true"></i></span>
                                </a>
                            @empty
                                <p class="hm-fd-empty">{{ __('dashboard.analytics.empty') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick access --}}
        <div class="hm-fd-card hm-fd-section">
            <div class="hm-fd-card__body">
                <div class="hm-fd-list-head">
                    <div>
                        <h4 class="hm-fd-card__title hm-fd-card__title--lg mb-1">{{ __('dashboard.quick_access') }}</h4>
                        <p class="hm-fd-card__subtitle">{{ __('dashboard.widgets.modules') }}</p>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    @foreach ($cards as $card)
                        <div class="col-sm-6 col-xl-4">
                            <a href="{{ $card->url }}" class="hm-fd-tile">
                                <span class="hm-fd-tile__head">
                                    <span class="hm-fd-tile__icon"><i class="bi {{ $card->icon }}" aria-hidden="true"></i></span>
                                    <span class="hm-fd-tile__arrow"><i class="bi bi-arrow-up-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" aria-hidden="true"></i></span>
                                </span>
                                <span class="hm-fd-tile__title">{{ $card->title }}</span>
                                @if (! empty($card->description))
                                    <span class="hm-fd-tile__desc">{{ $card->description }}</span>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.hmDashCharts = {
            locale: @json(app()->getLocale()),
            labels: {
                total: @json(__('dashboard.analytics.series.total')),
                pending: @json(__('dashboard.analytics.series.pending')),
                completed: @json(__('dashboard.analytics.series.completed')),
                complaints: @json(__('dashboard.analytics.modules.complaints')),
                inquiries: @json(__('dashboard.analytics.modules.inquiries')),
            },
            trend: @json(['labels' => $trend['labels'], 'total' => $trend['total']]),
            modules: @json($doughnutModules),
            status: @json($statusByModule),
            branches: @json($branchComparison),
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('js/hm-dashboard-analytics.js') }}?v={{ filemtime(public_path('js/hm-dashboard-analytics.js')) }}" defer></script>
@endpush
