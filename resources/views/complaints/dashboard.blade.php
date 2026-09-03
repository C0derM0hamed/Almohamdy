@extends('layouts.app')

@section('title', __('complaints.title'))
@section('sidebar_heading', __('complaints.title'))
@section('sidebar_subheading', __('complaints.dashboard_subtitle'))
@section('figma_page_header', 'true')

@push('workflow_styles')
    <link href="{{ asset('css/hm-complaints-figma.css') }}?v={{ filemtime(public_path('css/hm-complaints-figma.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-fm hm-cp hm-cp--figma-dashboard">
        <header class="cp-fm-head">
            <div class="cp-fm-topbar">
                <div class="hm-figma-crumb-row">
                    @include('layouts.partials.figma-sidebar-toggle')
                    <nav class="cp-fm-crumb" aria-label="{{ __('breadcrumbs.aria_label') }}">
                        <span>{{ __('dashboard.modules') }}</span>
                        <img src="{{ asset('images/figma/header/crumb-sep.svg') }}" alt="" width="18" height="18">
                        <strong>{{ __('complaints.title') }}</strong>
                    </nav>
                </div>
                @include('layouts.partials.figma-header-tools')
            </div>
            <div class="cp-fm-hero">
                <span class="cp-fm-hero__icon"><img src="{{ asset('images/figma/workflows/complaints.svg') }}" alt="" width="32" height="32"></span>
                <div><h1>{{ __('complaints.dashboard') }}</h1><p>{{ __('complaints.dashboard_subtitle') }}</p></div>
            </div>
        </header>

        <section class="cp-fm-insights" aria-label="{{ __('complaints.insights.aria_label') }}">
            <article class="cp-fm-insight cp-fm-insight--primary">
                <div class="cp-fm-insight__head">
                    <span class="cp-fm-insight__icon cp-fm-icon cp-fm-icon--chart" aria-hidden="true"><span></span><img src="{{ asset('images/figma/complaints-dashboard/chart-line.svg') }}" alt="" width="10" height="8"></span>
                    <p>{{ __('complaints.insights.processing_rate') }}</p>
                </div>
                <strong>{{ $insights['processing_rate'] }}%</strong>
            </article>

            <article class="cp-fm-insight cp-fm-insight--dark">
                <div class="cp-fm-insight__head">
                    <span class="cp-fm-insight__icon cp-fm-icon cp-fm-icon--active" aria-hidden="true"><img class="cp-fm-icon__box" src="{{ asset('images/figma/complaints-dashboard/active-box.svg') }}" alt="" width="18" height="18"><img class="cp-fm-icon__line" src="{{ asset('images/figma/complaints-dashboard/active-line.svg') }}" alt="" width="12" height="8"></span>
                    <p>{{ __('complaints.insights.most_active_department') }}</p>
                </div>
                <strong>{{ $insights['most_active_department'] }}</strong>
            </article>

            <article class="cp-fm-insight cp-fm-insight--primary">
                <div class="cp-fm-insight__head">
                    <span class="cp-fm-insight__icon cp-fm-icon cp-fm-icon--time" aria-hidden="true"><img class="cp-fm-icon__circle" src="{{ asset('images/figma/complaints-dashboard/time-circle.svg') }}" alt="" width="18" height="18"><img class="cp-fm-icon__hand" src="{{ asset('images/figma/complaints-dashboard/time-hand.svg') }}" alt="" width="6" height="5"></span>
                    <p>{{ __('complaints.insights.latest_update') }}</p>
                </div>
                <strong dir="ltr">{{ $insights['latest_update_label'] }}</strong>
            </article>
        </section>

        <section class="cp-fm-filter" aria-labelledby="complaintsFilterTitle">
            <h2 id="complaintsFilterTitle">{{ __('complaints.filters_title') }}</h2>
            <form method="GET" action="{{ route('modules.complaints') }}" class="cp-fm-filter__form">
                <div class="cp-fm-field cp-fm-field--search">
                    <label for="complaintSearch">{{ __('complaints.filters.search') }}</label>
                    <input type="search" id="complaintSearch" name="search" value="{{ $filters['search'] }}" placeholder="{{ __('complaints.filters.search_placeholder') }}" maxlength="100">
                </div>
                <div class="cp-fm-field cp-fm-field--status">
                    <label for="complaintStatus">{{ __('complaints.filters.status') }}</label>
                    <span class="cp-fm-select">
                        <select id="complaintStatus" name="status">
                            <option value="">{{ __('complaints.filters.all_statuses') }}</option>
                            <option value="0" @selected((string) $filters['status'] === '0')>{{ __('complaints.status.new') }}</option>
                            @foreach ($statusOptions as $statusOption)
                                <option value="{{ $statusOption->id }}" @selected((string) $filters['status'] === (string) $statusOption->id)>{{ $statusOption->localizedName() }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </span>
                </div>
                <button type="submit" class="cp-fm-search-btn">{{ __('complaints.search') }}</button>
                @if ($hasFilters)
                    <a href="{{ route('modules.complaints') }}" class="cp-fm-reset" aria-label="{{ __('complaints.reset') }}"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></a>
                @endif
            </form>
        </section>

        <section class="cp-fm-list" aria-labelledby="complaintsListTitle">
            <header class="cp-fm-list__head">
                <div class="cp-fm-list__title"><h2 id="complaintsListTitle">{{ __('complaints.dashboard') }}</h2><span>{{ __('complaints.complaints_count', ['count' => $complaints->total()]) }}</span></div>
                <div class="cp-fm-list__tools" aria-label="{{ __('complaints.table_tools') }}">
                    <button type="button" data-cp-sort><i class="bi bi-arrow-down-up" aria-hidden="true"></i>{{ __('complaints.sort') }}</button>
                    <a href="{{ request()->fullUrl() }}" aria-label="{{ __('complaints.refresh') }}"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i></a>
                    <button type="button" data-cp-export><i class="bi bi-download" aria-hidden="true"></i>{{ __('complaints.export') }}</button>
                    <button type="button" aria-label="{{ __('complaints.columns_settings') }}"><i class="bi bi-layout-three-columns" aria-hidden="true"></i></button>
                </div>
            </header>

            <div class="cp-fm-table-wrap">
                <table class="cp-fm-table" data-cp-table>
                    <thead><tr><th>{{ __('complaints.columns.complaint_no') }}</th><th>{{ __('complaints.columns.file_no') }}</th><th>{{ __('complaints.columns.complainant') }}</th><th>{{ __('complaints.columns.department') }}</th><th>{{ __('complaints.columns.date') }}</th><th>{{ __('complaints.columns.status') }}</th><th>{{ __('complaints.columns.priority') }}</th><th>{{ __('complaints.columns.actions') }}</th></tr></thead>
                    <tbody>
                        @forelse ($complaints as $complaint)
                            @php
                                $statusLabel = (int) $complaint->status === 0 ? __('complaints.status.new') : ($complaint->currentStatus?->localizedName() ?? '—');
                                $priorityClass = (int) $complaint->priority === 1 ? 'is-high' : 'is-low';
                                $departmentTone = ((int) $complaint->branches_departments_id % 3) + 1;
                                $avatarTone = ((int) $complaint->id % 3) + 1;
                                $complainantName = $complaint->localizedComplainantName() ?: '—';
                            @endphp
                            <tr data-complaint-id="{{ $complaint->id }}">
                                <td><a class="cp-fm-number" href="{{ route('modules.complaints.show', $complaint->id) }}">{{ $complaint->displayNumber() }}</a></td>
                                <td dir="ltr">{{ $complaint->file_number ? '#'.ltrim((string) $complaint->file_number, '#') : '—' }}</td>
                                <td><span class="cp-fm-person"><span class="cp-fm-avatar cp-fm-avatar--{{ $avatarTone }}">{{ mb_substr($complainantName, 0, 1) }}</span><span>{{ $complainantName }}</span></span></td>
                                <td><span class="cp-fm-department cp-fm-department--{{ $departmentTone }}">{{ $complaint->department?->localizedName() ?? '—' }}</span></td>
                                <td><span class="cp-fm-date" dir="ltr">{{ $complaint->formattedComplaintDate() }} <i class="bi bi-calendar3" aria-hidden="true"></i></span></td>
                                <td><span class="cp-fm-status cp-fm-status--{{ (int) $complaint->status }}"><i></i>{{ $statusLabel }}</span></td>
                                <td><span class="cp-fm-priority {{ $priorityClass }}">{{ $complaint->priorityLabel() }}</span></td>
                                <td><span class="cp-fm-actions"><a href="{{ route('modules.complaints.show', $complaint->id) }}"><i class="bi bi-eye" aria-hidden="true"></i>{{ __('complaints.view_detail') }}</a><a href="{{ route('modules.complaints.show', ['complaint' => $complaint->id, 'timeline' => 1]) }}"><i class="bi bi-clock-history" aria-hidden="true"></i>{{ __('complaints.view_timeline') }}</a></span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="cp-fm-empty">{{ $hasFilters ? __('complaints.no_results') : __('complaints.no_complaints') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($complaints->total() > 0)
                <footer class="cp-fm-pager">
                    <p>{!! __('complaints.results_summary', ['shown' => '<strong>'.$complaints->count().'</strong>', 'total' => '<strong>'.$complaints->total().'</strong>']) !!}</p>
                    {{ $complaints->links('pagination.hm') }}
                </footer>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.querySelector('[data-cp-table]');
    if (!table) return;
    document.querySelector('[data-cp-sort]')?.addEventListener('click', function () {
        const body = table.tBodies[0];
        const rows = Array.from(body.querySelectorAll('tr[data-complaint-id]'));
        const descending = table.dataset.sortDirection !== 'desc';
        rows.sort((a, b) => (Number(a.dataset.complaintId) - Number(b.dataset.complaintId)) * (descending ? -1 : 1));
        rows.forEach(row => body.appendChild(row));
        table.dataset.sortDirection = descending ? 'desc' : 'asc';
    });
    document.querySelector('[data-cp-export]')?.addEventListener('click', function () {
        const rows = Array.from(table.rows).map(row => Array.from(row.cells).map(cell => `"${cell.innerText.replaceAll('"', '""').trim()}"`).join(','));
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob(["\uFEFF" + rows.join('\n')], { type: 'text/csv;charset=utf-8' }));
        link.download = 'complaints.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    });
});
</script>
@endpush
