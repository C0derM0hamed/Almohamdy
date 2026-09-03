@extends('layouts.app')

@section('title', __('licenses.dashboard'))
@section('sidebar_heading', __('licenses.title'))
@section('sidebar_subheading', __('licenses.dashboard_subtitle'))
@section('figma_page_header', true)

@push('styles')
<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $url = static fn ($name, $params = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : '#';
    $nameOf = static function ($item) {
        if (! $item) return '—'; if (is_string($item)) return $item;
        if (method_exists($item, 'displayName')) return $item->displayName();
        if (method_exists($item, 'localizedName')) return $item->localizedName();
        $field = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
        return data_get($item, $field) ?: data_get($item, 'name') ?: '—';
    };
    $filters = array_merge(['branch_id'=>'','authority_id'=>'','type_id'=>'','status_id'=>'','expiry_from'=>'','expiry_to'=>''], $filters ?? request()->only(['branch_id','authority_id','type_id','status_id','expiry_from','expiry_to']));
    $metrics = array_merge(['total'=>0,'active'=>0,'near_expiry'=>0,'under_renewal'=>0,'expired'=>0], $kpis ?? $metrics ?? []);
    $finance = array_merge(['open'=>0,'paid'=>0,'needs_documents'=>0,'in_progress'=>0,'average_close_days'=>0], $financeKpis ?? $financeMetrics ?? []);
    $risks = $topRisks ?? $criticalLicenses ?? collect();
    $chartPayload = $charts ?? $chartData ?? [];
@endphp
<div class="hm-fm hm-licenses">
    @include('layouts.partials.figma-module-header', ['compact'=>true,'title'=>__('licenses.dashboard'),'crumbs'=>[['label'=>__('dashboard.modules')],['label'=>__('licenses.title')],['label'=>__('licenses.dashboard')]]])
    @include('licenses.partials.page-header', [
        'title'=>__('licenses.dashboard'),'subtitle'=>__('licenses.dashboard_subtitle'),'icon'=>'bi-speedometer2',
        'actions'=>new \Illuminate\Support\HtmlString('<a class="lic-btn" href="'.e($url('modules.licenses.index')).'"><i class="bi bi-list-check"></i>'.e(__('licenses.index')).'</a>'),
    ])
    @include('licenses.partials.feedback')

    <section class="lic-toolbar lic-no-print" aria-labelledby="dashboardFiltersTitle">
        <h2 id="dashboardFiltersTitle" class="lic-toolbar__title"><i class="bi bi-funnel"></i>{{ __('licenses.filters.title') }}</h2>
        <form method="GET" action="{{ $url('modules.licenses.dashboard') }}"><div class="lic-filter-grid">
            @foreach ([
                ['branch_id',__('licenses.filters.branch'),$branchOptions ?? $branches ?? collect()],
                ['authority_id',__('licenses.filters.authority'),$authorityOptions ?? $authorities ?? collect()],
                ['type_id',__('licenses.filters.type'),$typeOptions ?? $types ?? collect()],
                ['status_id',__('licenses.filters.status'),$statusOptions ?? $statuses ?? collect()],
            ] as [$field,$label,$options])
                <div class="lic-field"><label for="dash_{{ $field }}">{{ $label }}</label><select id="dash_{{ $field }}" name="{{ $field }}" class="form-select"><option value="">{{ __('licenses.all') }}</option>@foreach($options as $option)<option value="{{ $option->id }}" @selected((string)$filters[$field]===(string)$option->id)>{{ $nameOf($option) }}</option>@endforeach</select></div>
            @endforeach
            <div class="lic-field"><label for="dash_from">{{ __('licenses.filters.expiry_from') }}</label><input id="dash_from" type="date" name="expiry_from" value="{{ $filters['expiry_from'] }}" class="form-control"></div>
            <div class="lic-field"><label for="dash_to">{{ __('licenses.filters.expiry_to') }}</label><input id="dash_to" type="date" name="expiry_to" value="{{ $filters['expiry_to'] }}" class="form-control"></div>
            <div class="lic-filter-actions"><button class="lic-btn lic-btn--primary" type="submit">{{ __('licenses.apply_filters') }}</button><a class="lic-btn" href="{{ $url('modules.licenses.dashboard') }}">{{ __('licenses.reset') }}</a></div>
        </div></form>
    </section>

    <section class="lic-stat-grid" aria-label="{{ __('licenses.dashboard') }}">
        @foreach ([['total','bi-files',''],['active','bi-check-circle','active'],['near_expiry','bi-clock-history','warning'],['under_renewal','bi-arrow-repeat','violet'],['expired','bi-exclamation-octagon','danger']] as [$key,$icon,$tone])
            <article class="lic-stat {{ $tone ? 'lic-stat--'.$tone : '' }}"><span class="lic-stat__icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span><span class="lic-stat__copy"><span class="lic-stat__label">{{ __('licenses.dashboard_cards.'.$key) }}</span><strong class="lic-stat__value">{{ (int)($metrics[$key] ?? 0) }}</strong><span class="lic-stat__hint">{{ __('licenses.dashboard_cards.'.$key) }}</span></span></article>
        @endforeach
    </section>

    <section class="lic-chart-grid" aria-label="{{ __('licenses.dashboard') }}">
        @foreach ([['branchChart','by_branch','doughnut'],['authorityChart','by_authority','bar'],['typeChart','by_type','bar'],['expiryChart','expiry_windows','line']] as [$id,$key,$type])
            <article class="lic-panel lic-chart"><h2 class="lic-panel__title">{{ __('licenses.charts.'.$key) }}</h2><canvas id="{{ $id }}" data-chart-key="{{ $key }}" data-chart-type="{{ $type }}" role="img" aria-label="{{ __('licenses.charts.'.$key) }}"></canvas></article>
        @endforeach
    </section>

    <div class="lic-two-column">
        <section class="lic-panel" aria-labelledby="topRisksTitle">
            <h2 id="topRisksTitle" class="lic-panel__title"><i class="bi bi-exclamation-triangle"></i>{{ __('licenses.risks.title') }}</h2>
            @forelse ($risks as $risk)
                @php $licenseRecord = data_get($risk,'license') ?: $risk; $days = data_get($risk,'days_remaining'); @endphp
                <article class="lic-risk"><div><a class="lic-table__primary" href="{{ $url('modules.licenses.show', $licenseRecord->getRouteKey()) }}">{{ $licenseRecord->title ?: $licenseRecord->license_number ?: '#'.$licenseRecord->id }}</a><span class="lic-table__sub">{{ $nameOf($licenseRecord->authority ?? null) }} · {{ $nameOf($licenseRecord->renewalStage ?? $licenseRecord->stage ?? null) }}</span></div><span class="lic-risk__days">{{ $days !== null && $days < 0 ? __('licenses.risks.overdue_days',['count'=>abs($days)]) : __('licenses.charts.days',['count'=>$days ?? '—']) }}</span></article>
            @empty <div class="lic-empty">{{ __('licenses.risks.empty') }}</div> @endforelse
        </section>
        <section class="lic-panel" aria-labelledby="financeKpisTitle">
            <div class="lic-panel__head"><h2 id="financeKpisTitle" class="lic-panel__title"><i class="bi bi-cash-coin"></i>{{ __('licenses.payments.title') }}</h2><a class="lic-btn lic-btn--sm" href="{{ $url('modules.licenses.finance.index') }}">{{ __('licenses.view') }}</a></div>
            <div class="lic-finance-grid lic-finance-grid--compact">
                @foreach ([['open','open_payments'],['paid','paid_payments'],['needs_documents','needs_documents'],['average_close_days','average_close']] as [$key,$label])
                    <article class="lic-mini-stat"><span>{{ __('licenses.dashboard_cards.'.$label) }}</span><strong>{{ $finance[$key] ?? 0 }}@if($key==='average_close_days')<small> d</small>@endif</strong></article>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    var payload = @json($chartPayload);
    var palette = ['#1f5f96','#16835d','#bd7410','#c73939','#7356a6','#4b9fc8','#6b8295'];
    function normalized(key) {
        var raw = payload[key] || {};
        if (Array.isArray(raw)) return { labels: raw.map(function (v) { return v.label || v.name || ''; }), data: raw.map(function (v) { return Number(v.value ?? v.total ?? 0); }) };
        return { labels: raw.labels || [], data: raw.data || raw.values || [] };
    }
    document.querySelectorAll('[data-chart-key]').forEach(function (canvas) {
        if (typeof Chart === 'undefined') return;
        var values = normalized(canvas.dataset.chartKey);
        new Chart(canvas, { type: canvas.dataset.chartType, data: { labels: values.labels, datasets: [{ label: canvas.getAttribute('aria-label'), data: values.data, backgroundColor: canvas.dataset.chartType === 'line' ? 'rgba(31,95,150,.16)' : palette, borderColor: canvas.dataset.chartType === 'line' ? '#1f5f96' : palette, borderWidth: 2, fill: canvas.dataset.chartType === 'line', tension: .32 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: canvas.dataset.chartType === 'doughnut', position: 'bottom' } }, scales: canvas.dataset.chartType === 'doughnut' ? {} : { y: { beginAtZero: true, ticks: { precision: 0 } } } } });
    });
})();
</script>
@endpush
