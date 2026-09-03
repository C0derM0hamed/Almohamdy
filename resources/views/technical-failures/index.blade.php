@extends('layouts.app')
@section('title', __('technical_failures.title'))
@section('figma_page_header', 'true')

@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush

@section('content')
<div class="hm-fm hm-workflow hm-technical-list">
    @include('layouts.partials.figma-module-header', [
        'crumbs' => [
            ['label' => __('dashboard.modules')],
            ['label' => __('technical_failures.title')],
        ],
        'title' => __('technical_failures.title'),
        'subtitle' => __('technical_failures.subtitle'),
        'heroIconSrc' => asset('images/figma/technical-failures/hero.svg'),
        'heroIconSize' => 32,
        'actionUrl' => route('modules.technical-failures.create'),
        'actionLabel' => __('technical_failures.create'),
        'actionIconSrc' => asset('images/figma/technical-failures/add.svg'),
    ])

    <form method="get" class="wf-search-panel" aria-labelledby="technicalSearchTitle">
        <h2 id="technicalSearchTitle">{{ __('technical_failures.search_title') }}</h2>
        <div class="wf-filter-grid wf-filter-grid--technical">
            <div class="wf-field wf-field--wide">
                <label for="search">{{ __('technical_failures.search_number') }}</label>
                <input id="search" name="search" value="{{ $filters['search'] }}" placeholder="{{ __('technical_failures.search_number_placeholder') }}">
            </div>
            <div class="wf-field">
                <label for="status">{{ __('technical_failures.status') }}</label>
                <span class="wf-select-wrap">
                    <select id="status" name="status">
                        <option value="">{{ __('technical_failures.all_statuses') }}</option>
                        <option value="0" @selected($filters['status'] === 0)>{{ __('technical_failures.new') }}</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}" @selected($filters['status'] === (int) $status->id)>{{ app()->getLocale() === 'ar' ? $status->name_ar : $status->name_en }}</option>
                        @endforeach
                    </select>
                    <img src="{{ asset('images/figma/technical-failures/select.svg') }}" alt="" width="20" height="20">
                </span>
            </div>
            <div class="wf-field">
                <label for="from">{{ __('technical_failures.from') }}</label>
                <span class="wf-date-wrap">
                    <input type="date" id="from" name="from" value="{{ $filters['from'] }}">
                    <img src="{{ asset('images/figma/technical-failures/calendar.svg') }}" alt="" width="16" height="16">
                </span>
            </div>
            <div class="wf-field">
                <label for="to">{{ __('technical_failures.to') }}</label>
                <span class="wf-date-wrap">
                    <input type="date" id="to" name="to" value="{{ $filters['to'] }}">
                    <img src="{{ asset('images/figma/technical-failures/calendar.svg') }}" alt="" width="16" height="16">
                </span>
            </div>
            <button class="wf-search-btn" type="submit">{{ __('technical_failures.search') }}</button>
        </div>
    </form>

    <section class="wf-table-panel" aria-labelledby="technicalListTitle">
        <header class="wf-table-head">
            <div class="wf-table-heading">
                <h2 id="technicalListTitle">{{ __('technical_failures.title') }}</h2>
                <span>{{ trans_choice('technical_failures.notices_count', $notices->total(), ['count' => $notices->total()]) }}</span>
            </div>
            <div class="wf-table-tools">
                <button type="button" data-wf-export aria-label="{{ __('technical_failures.export') }}"><i class="bi bi-download" aria-hidden="true"></i><span>{{ __('technical_failures.export') }}</span></button>
                <a href="{{ request()->fullUrl() }}" aria-label="{{ __('technical_failures.refresh') }}"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i></a>
                <button type="button" data-wf-sort aria-label="{{ __('technical_failures.sort') }}"><i class="bi bi-arrow-down-up" aria-hidden="true"></i><span>{{ __('technical_failures.sort') }}</span></button>
            </div>
        </header>

        <div class="table-responsive">
            <table class="wf-table" data-wf-table>
                <thead><tr><th>{{ __('technical_failures.number') }}</th><th>{{ __('technical_failures.date') }}</th><th>{{ __('technical_failures.notice') }}</th><th>{{ __('technical_failures.platform') }}</th><th>{{ __('technical_failures.service_type') }}</th><th>{{ __('technical_failures.status') }}</th><th>{{ __('technical_failures.actions') }}</th></tr></thead>
                <tbody>
                    @forelse($notices as $notice)
                        <tr data-record-id="{{ $notice->id }}">
                            <td><span class="wf-record-code">#{{ $notice->id }}</span></td>
                            <td dir="ltr">{{ date('Y-m-d', (int) $notice->date_time) }}</td>
                            <td><span class="wf-notice-cell"><span class="wf-notice-avatar">{{ mb_substr((string) $notice->notice, 0, 1) }}</span><span>{{ $notice->notice }}</span></span></td>
                            <td><span class="wf-link-text">{{ app()->getLocale() === 'ar' ? $notice->platform_name_ar : $notice->platform_name_en }}</span></td>
                            <td><span class="wf-service-chip">{{ app()->getLocale() === 'ar' ? $notice->service_type_name_ar : $notice->service_type_name_en }}</span></td>
                            <td><span class="wf-status" style="--wf-status-color: {{ $notice->status_color ?: '#6366f1' }}">{{ (int) $notice->status === 0 ? __('technical_failures.new') : (app()->getLocale() === 'ar' ? $notice->status_name_ar : $notice->status_name_en) }}</span></td>
                            <td class="wf-actions">
                                <a class="wf-view-btn" href="{{ route('modules.technical-failures.show', $notice->id) }}"><i class="bi bi-eye" aria-hidden="true"></i>{{ __('technical_failures.view_details') }}</a>
                                <a class="wf-file-btn" href="{{ route('modules.technical-failures.pdf', $notice->id) }}" aria-label="{{ __('technical_failures.pdf') }}"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="wf-empty">{{ __('technical_failures.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('layouts.partials.figma.pagination', [
            'paginator' => $notices,
            'summaryKey' => 'technical_failures.results_summary',
        ])
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('[data-wf-table]');
    if (!table) return;

    document.querySelector('[data-wf-sort]')?.addEventListener('click', () => {
        const body = table.tBodies[0];
        const rows = [...body.querySelectorAll('tr[data-record-id]')];
        const descending = table.dataset.sortDirection !== 'desc';
        rows.sort((a, b) => (Number(a.dataset.recordId) - Number(b.dataset.recordId)) * (descending ? -1 : 1));
        rows.forEach(row => body.appendChild(row));
        table.dataset.sortDirection = descending ? 'desc' : 'asc';
    });

    document.querySelector('[data-wf-export]')?.addEventListener('click', () => {
        const rows = [...table.rows].map(row => [...row.cells].map(cell => `"${cell.innerText.replaceAll('"', '""').trim()}"`).join(','));
        const blob = new Blob(["\uFEFF" + rows.join('\n')], { type: 'text/csv;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'technical-failures.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    });
});
</script>
@endpush
