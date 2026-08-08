@extends('layouts.app')

@section('title', __('dashboard.title'))

@push('styles')
    <link href="{{ asset('css/hm-dashboard-analytics.css') }}?v={{ filemtime(public_path('css/hm-dashboard-analytics.css')) }}" rel="stylesheet">
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
    @endphp

    <div class="hm-hope-dashboard hm-analytics-dashboard">
        <div class="card border-0 shadow-sm mb-4 hm-hope-welcome">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <p class="text-muted mb-1">{{ __('dashboard.hello') }}</p>
                        <h2 class="mb-1">{{ $userName }}</h2>
                        <p class="mb-0 text-muted">{{ __('dashboard.analytics.subtitle') }}</p>
                    </div>
                    <div class="hm-hope-welcome__icon" aria-hidden="true">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI tiles --}}
        <div class="row g-3 mb-4">
            @foreach ($analytics['kpis'] as $kpi)
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100 hm-kpi-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="hm-kpi-icon hm-kpi-icon--{{ $kpi['variant'] }}">
                                <i class="bi {{ $kpi['icon'] }}" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="mb-1 text-muted small">{{ __('dashboard.analytics.kpis.'.$kpi['key']) }}</p>
                                <h4 class="mb-0">{{ number_format($kpi['value']) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Trend + module breakdown --}}
        <div class="row g-3 mb-4">
            <div class="col-xl-8">
                <div class="card h-100 hm-chart-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="hm-chart-title mb-0">{{ __('dashboard.analytics.charts.trend') }}</h5>
                            <span class="text-muted small">
                                {{ __('dashboard.analytics.charts.trend_meta', ['last7' => number_format($trend['last7']), 'last30' => number_format($trend['last30'])]) }}
                            </span>
                        </div>
                        <div class="hm-chart-box hm-chart-box--tall">
                            <canvas id="hmDashTrend" aria-label="{{ __('dashboard.analytics.charts.trend') }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100 hm-chart-card">
                    <div class="card-body">
                        <h5 class="hm-chart-title mb-3">{{ __('dashboard.analytics.charts.modules') }}</h5>
                        @if (array_sum(array_column($moduleTotals, 'total')) > 0)
                            <div class="hm-chart-box hm-chart-box--tall">
                                <canvas id="hmDashModules" aria-label="{{ __('dashboard.analytics.charts.modules') }}"></canvas>
                            </div>
                        @else
                            <p class="text-muted mb-0">{{ __('dashboard.analytics.empty') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Pending vs completed + attention --}}
        <div class="row g-3 mb-4">
            <div class="col-xl-8">
                <div class="card h-100 hm-chart-card">
                    <div class="card-body">
                        <h5 class="hm-chart-title mb-3">{{ __('dashboard.analytics.charts.status') }}</h5>
                        @if ($statusByModule !== [])
                            <div class="hm-chart-box hm-chart-box--tall">
                                <canvas id="hmDashStatus" aria-label="{{ __('dashboard.analytics.charts.status') }}"></canvas>
                            </div>
                        @else
                            <p class="text-muted mb-0">{{ __('dashboard.analytics.empty') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100 hm-chart-card">
                    <div class="card-body">
                        <h5 class="hm-chart-title mb-3">{{ __('dashboard.analytics.attention_title') }}</h5>
                        @forelse ($analytics['attention'] as $item)
                            <a href="{{ $item['url'] ?? '#' }}" class="hm-attention-row d-flex align-items-center justify-content-between text-decoration-none">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="hm-attention-dot" aria-hidden="true"></span>
                                    <span class="hm-attention-label">{{ $item['label'] }}</span>
                                </span>
                                <span class="badge hm-attention-badge">{{ number_format($item['count']) }}</span>
                            </a>
                        @empty
                            <p class="text-muted mb-0">{{ __('dashboard.analytics.attention_empty') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Branch comparison (administrators only) + latest activity --}}
        <div class="row g-3 mb-4">
            @if ($branchComparison !== null)
                <div class="col-xl-7">
                    <div class="card h-100 hm-chart-card">
                        <div class="card-body">
                            <h5 class="hm-chart-title mb-3">{{ __('dashboard.analytics.charts.branches') }}</h5>
                            <div class="hm-chart-box hm-chart-box--tall">
                                <canvas id="hmDashBranches" aria-label="{{ __('dashboard.analytics.charts.branches') }}"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="{{ $branchComparison !== null ? 'col-xl-5' : 'col-12' }}">
                <div class="card h-100 hm-chart-card">
                    <div class="card-body">
                        <h5 class="hm-chart-title mb-3">{{ __('dashboard.analytics.latest_title') }}</h5>
                        @forelse ($analytics['latest'] as $item)
                            <a href="{{ $item['url'] ?? '#' }}" class="hm-latest-row d-flex align-items-center justify-content-between gap-2 text-decoration-none">
                                <span class="hm-latest-main">
                                    <span class="hm-latest-title d-block">{{ $item['title'] }}</span>
                                    <span class="hm-latest-module text-muted small">{{ $item['label'] }}</span>
                                </span>
                                @if ($item['date'])
                                    <span class="hm-latest-date text-muted small" dir="ltr">{{ $item['date'] }}</span>
                                @endif
                            </a>
                        @empty
                            <p class="text-muted mb-0">{{ __('dashboard.analytics.empty') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick access (unchanged behaviour) --}}
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h3 class="mb-1">{{ __('dashboard.quick_access') }}</h3>
                <p class="text-muted mb-0">{{ __('dashboard.modules') }}</p>
            </div>
        </div>
        <div class="row g-3">
            @foreach ($cards as $card)
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <a href="{{ $card->url }}" class="text-decoration-none hm-hope-module-link">
                        <div class="card h-100 hm-hope-module-card">
                            <div class="card-body d-flex flex-column">
                                <div class="hm-hope-module-card__icon">
                                    <i class="bi {{ $card->icon }}" aria-hidden="true"></i>
                                </div>
                                <h5 class="mt-3 mb-2 text-dark">{{ $card->title }}</h5>
                                @if (! empty($card->description))
                                    <p class="text-muted small mb-0 flex-grow-1">{{ $card->description }}</p>
                                @endif
                                <span class="hm-hope-module-card__arrow mt-3" aria-hidden="true">
                                    <i class="bi bi-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
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
