@extends('layouts.app')

@section('title', __('licenses.index'))
@section('sidebar_heading', __('licenses.title'))
@section('sidebar_subheading', __('licenses.subtitle'))
@section('figma_page_header', true)

@push('styles')
<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $url = static fn ($name, $params = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : '#';
    $nameOf = static function ($item) {
        if (! $item) return '—';
        if (method_exists($item, 'displayName')) return $item->displayName();
        if (method_exists($item, 'localizedName')) return $item->localizedName();
        $field = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
        return data_get($item, $field) ?: data_get($item, 'name') ?: data_get($item, 'hr_name') ?: data_get($item, 'full_name') ?: '—';
    };
    $dateOf = static function ($value) {
        if (! $value) return '—';
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : substr((string) $value, 0, 10);
    };
    $filters = array_merge(['search'=>'','branch_id'=>'','authority_id'=>'','type_id'=>'','responsible_user_id'=>'','status_id'=>'','expiry_from'=>'','expiry_to'=>'','expiry_window'=>''], $filters ?? request()->only(['search','branch_id','authority_id','type_id','responsible_user_id','status_id','expiry_from','expiry_to','expiry_window']));
    $items = $licenses ?? collect();
    $authoritiesList = $authorityOptions ?? $authorities ?? collect();
    $typesList = $typeOptions ?? $licenseTypes ?? collect();
    $branchesList = $branchOptions ?? $branches ?? collect();
    $responsiblesList = $responsibleOptions ?? $users ?? collect();
    $statusesList = $statusOptions ?? $statuses ?? collect();
    $metrics = array_merge(['total'=>method_exists($items, 'total') ? $items->total() : count($items), 'active'=>0, 'near_expiry'=>0, 'under_renewal'=>0, 'expired'=>0], $statusCounters ?? $counters ?? $kpis ?? []);
    $canAdminUi = (bool) ($canAdmin ?? $permissions['admin'] ?? ((int) session('hr_user_level') === 3));
    $canExportUi = (bool) ($canExport ?? $permissions['export'] ?? $canAdminUi);
    $routeQuery = array_filter($filters, static fn ($value) => $value !== '' && $value !== null);
@endphp

<div class="hm-fm hm-licenses {{ app()->getLocale() === 'ar' ? 'hm-gc--rtl' : 'hm-gc--ltr' }}">
    @include('layouts.partials.figma-module-header', [
        'compact' => true,
        'title' => __('licenses.index'),
        'crumbs' => [['label' => __('dashboard.modules')], ['label' => __('licenses.title')]],
    ])

    @php
        $headerActions = '<a class="lic-btn" href="'.e($url('modules.licenses.dashboard')).'"><i class="bi bi-grid-1x2"></i>'.e(__('licenses.dashboard')).'</a>';
        if ($canAdminUi) $headerActions .= '<a class="lic-btn lic-btn--primary" href="'.e($url('modules.licenses.create')).'"><i class="bi bi-plus-lg"></i>'.e(__('licenses.create')).'</a>';
    @endphp
    @include('licenses.partials.page-header', [
        'title' => __('licenses.index'),
        'subtitle' => __('licenses.subtitle'),
        'actions' => new \Illuminate\Support\HtmlString($headerActions),
        'notificationItems' => $recentNotifications ?? collect(),
        'notificationUnreadCount' => $unreadNotificationCount ?? 0,
    ])
    @include('licenses.partials.feedback')

    <section class="lic-stat-grid" aria-label="{{ __('licenses.dashboard') }}">
        @foreach ([
            ['total','bi-files',''], ['active','bi-check-circle','active'], ['near_expiry','bi-clock-history','warning'],
            ['under_renewal','bi-arrow-repeat','violet'], ['expired','bi-exclamation-octagon','danger'],
        ] as [$key,$icon,$tone])
            @php $counterStatus = $key === 'total' ? null : $statusesList->firstWhere('code', $key); @endphp
            <a class="lic-stat {{ $tone ? 'lic-stat--'.$tone : '' }} text-decoration-none" href="{{ $counterStatus ? $url('modules.licenses.index', ['status_id' => $counterStatus->id]) : $url('modules.licenses.index') }}">
                <span class="lic-stat__icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                <span class="lic-stat__copy">
                    <span class="lic-stat__label">{{ __('licenses.dashboard_cards.'.$key) }}</span>
                    <strong class="lic-stat__value">{{ (int) ($metrics[$key] ?? 0) }}</strong>
                    <span class="lic-stat__hint">{{ __('licenses.dashboard_cards.'.$key) }}</span>
                </span>
            </a>
        @endforeach
    </section>

    <section class="lic-toolbar" aria-labelledby="licenseFiltersTitle">
        <h2 id="licenseFiltersTitle" class="lic-toolbar__title"><i class="bi bi-funnel" aria-hidden="true"></i>{{ __('licenses.filters.title') }}</h2>
        <form method="GET" action="{{ $url('modules.licenses.index') }}">
            <div class="lic-filter-grid">
                <div class="lic-field">
                    <label for="licenseSearch">{{ __('licenses.filters.search') }}</label>
                    <input id="licenseSearch" type="search" name="search" maxlength="150" value="{{ $filters['search'] }}" placeholder="{{ __('licenses.filters.search_placeholder') }}" class="form-control">
                </div>
                @foreach ([
                    ['branch_id', __('licenses.filters.branch'), $branchesList],
                    ['authority_id', __('licenses.filters.authority'), $authoritiesList],
                    ['type_id', __('licenses.filters.type'), $typesList],
                    ['responsible_user_id', __('licenses.filters.responsible'), $responsiblesList],
                    ['status_id', __('licenses.filters.status'), $statusesList],
                ] as [$field,$label,$options])
                    <div class="lic-field">
                        <label for="license_{{ $field }}">{{ $label }}</label>
                        <select id="license_{{ $field }}" name="{{ $field }}" class="form-select">
                            <option value="">{{ __('licenses.all') }}</option>
                            @foreach ($options as $option)
                                @php $optionId = $option->hr_id ?? $option->id; @endphp
                                <option value="{{ $optionId }}" @selected((string) $filters[$field] === (string) $optionId)>{{ $nameOf($option) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="lic-field">
                    <label for="expiry_from">{{ __('licenses.filters.expiry_from') }}</label>
                    <input id="expiry_from" type="date" name="expiry_from" value="{{ $filters['expiry_from'] }}" class="form-control">
                </div>
                <div class="lic-field">
                    <label for="expiry_to">{{ __('licenses.filters.expiry_to') }}</label>
                    <input id="expiry_to" type="date" name="expiry_to" value="{{ $filters['expiry_to'] }}" class="form-control">
                </div>
                <div class="lic-field">
                    <label for="expiry_window">{{ __('licenses.filters.window') }}</label>
                    <select id="expiry_window" name="expiry_window" class="form-select">
                        <option value="">{{ __('licenses.all') }}</option>
                        @foreach (['30'=>'days_30','60'=>'days_60','90'=>'days_90','expired'=>'expired'] as $value => $label)
                            <option value="{{ $value }}" @selected((string) $filters['expiry_window'] === (string) $value)>{{ __('licenses.filters.'.$label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lic-filter-actions">
                    <button type="submit" class="lic-btn lic-btn--primary"><i class="bi bi-search" aria-hidden="true"></i>{{ __('licenses.apply_filters') }}</button>
                    <a href="{{ $url('modules.licenses.index') }}" class="lic-btn">{{ __('licenses.reset') }}</a>
                </div>
            </div>
        </form>
    </section>

    <section class="lic-panel" aria-labelledby="licenseResultsTitle">
        <div class="lic-panel__head">
            <h2 id="licenseResultsTitle" class="lic-panel__title"><i class="bi bi-list-check" aria-hidden="true"></i>{{ __('licenses.results', ['count' => method_exists($items, 'total') ? $items->total() : count($items)]) }}</h2>
            @if ($canExportUi)
                <div class="lic-results-tools lic-no-print">
                    <form method="GET" class="lic-report-tools" aria-label="{{ __('licenses.export') }}">
                        @foreach ($routeQuery as $key => $value)
                            @if ($key !== 'report')
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label class="visually-hidden" for="licenseExportReport">{{ __('licenses.reports.title') }}</label>
                        <select id="licenseExportReport" name="report" class="form-select form-select-sm" aria-label="{{ __('licenses.reports.title') }}">
                            @foreach (['licenses', 'payments', 'alerts', 'responsibilities'] as $report)
                                <option value="{{ $report }}" @selected(($filters['report'] ?? 'licenses') === $report)>{{ __('licenses.reports.'.$report) }}</option>
                            @endforeach
                        </select>
                        <button class="lic-btn lic-btn--sm" formaction="{{ $url('modules.licenses.export', ['format' => 'pdf']) }}" type="submit"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>{{ __('licenses.export_pdf') }}</button>
                        <button class="lic-btn lic-btn--sm" formaction="{{ $url('modules.licenses.export', ['format' => 'xls']) }}" type="submit"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>{{ __('licenses.export_excel') }}</button>
                        <button class="lic-btn lic-btn--sm" formaction="{{ $url('modules.licenses.export', ['format' => 'csv']) }}" type="submit"><i class="bi bi-filetype-csv" aria-hidden="true"></i>{{ __('licenses.export_csv') }}</button>
                    </form>
                </div>
            @endif
        </div>
        <div class="lic-table-wrap">
            <table class="lic-table lic-table--licenses">
                <thead><tr>
                    <th>{{ __('licenses.fields.license_number') }}</th><th>{{ __('licenses.fields.type') }}</th>
                    <th>{{ __('licenses.fields.authority') }}</th><th>{{ __('licenses.fields.branches') }}</th>
                    <th>{{ __('licenses.fields.responsible') }}</th><th>{{ __('licenses.fields.expiry_date') }}</th>
                    <th>{{ __('licenses.fields.status') }}</th><th>{{ __('licenses.fields.renewal_stage') }}</th><th>{{ __('licenses.actions') }}</th>
                </tr></thead>
                <tbody>
                @forelse ($items as $item)
                    @php
                        $status = $item->statusRelation ?? $item->status ?? null;
                        $statusKey = data_get($status, 'key') ?: data_get($status, 'code') ?: data_get($status, 'slug') ?: 'unknown';
                        $alertWindow = app(\App\Support\Licenses\LicenseAlertWindow::class)->for($item->expiry_date);
                        $alertStatusKey = match ($alertWindow) {
                            \App\Support\Licenses\LicenseAlertWindow::YELLOW,
                            \App\Support\Licenses\LicenseAlertWindow::SIXTY_DAYS => 'near_expiry',
                            \App\Support\Licenses\LicenseAlertWindow::RED,
                            \App\Support\Licenses\LicenseAlertWindow::EXPIRED => 'expired',
                            default => 'active',
                        };
                        $statusLabel = $nameOf($status);
                        $responsible = $item->responsibleUser ?? $item->responsible ?? null;
                        $itemTitle = $item->title ?: $nameOf($item->licenseType ?? $item->type ?? null);
                    @endphp
                    <tr>
                        <td><a class="lic-table__primary lic-sensitive" href="{{ $url('modules.licenses.show', $item->getRouteKey()) }}">{{ $item->license_number ?: '#'.$item->id }}</a><span class="lic-table__sub">{{ $itemTitle }}</span></td>
                        <td>{{ $nameOf($item->licenseType ?? $item->type ?? null) }}</td>
                        <td>{{ $nameOf($item->authority ?? null) }}</td>
                        <td><div class="lic-chip-list">@forelse (($item->branches ?? collect()) as $branch)<span class="lic-chip">{{ $nameOf($branch) }}</span>@empty — @endforelse</div></td>
                        <td>{{ $nameOf($responsible) }}</td>
                        <td class="lic-sensitive">{{ $dateOf($item->expiry_date) }}</td>
                        <td><span class="lic-status lic-status--{{ $alertStatusKey }}" title="{{ $statusLabel }}">{{ $statusLabel }}</span></td>
                        <td>{{ $nameOf($item->renewalStage ?? $item->stage ?? null) }}</td>
                        <td><div class="lic-table__actions"><a class="lic-btn lic-btn--sm" href="{{ $url('modules.licenses.show', $item->getRouteKey()) }}"><i class="bi bi-eye"></i>{{ __('licenses.view') }}</a>@if($canAdminUi)<a class="lic-btn lic-btn--sm" href="{{ $url('modules.licenses.edit', $item->getRouteKey()) }}" aria-label="{{ __('licenses.edit') }}"><i class="bi bi-pencil"></i></a>@endif</div></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="lic-empty">{{ array_filter($filters) ? __('licenses.no_results') : __('licenses.empty') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($items, 'links') && method_exists($items, 'total') && $items->total() > 0)
            <div class="lic-pagination"><span>{{ __('licenses.results', ['count' => $items->total()]) }}</span>{{ $items->withQueryString()->links('pagination.hm') }}</div>
        @endif
    </section>
</div>
@endsection
