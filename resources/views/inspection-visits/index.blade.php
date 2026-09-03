@extends('layouts.app')

@section('title', __('inspection_visits.list'))
@section('sidebar_heading', __('inspection_visits.title'))
@section('sidebar_subheading', __('inspection_visits.subtitle'))
@section('figma_page_header', true)

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inspection-visits.css') }}?v={{ filemtime(public_path('css/hm-inspection-visits.css')) }}" rel="stylesheet">
@endpush

@section('content')
    @php $isRtl = app()->getLocale() === 'ar'; @endphp
    <div class="hm-fm hm-gc hm-iv hm-corporate-list {{ $isRtl ? 'hm-gc--rtl' : 'hm-gc--ltr' }}" data-gc-rtl="{{ $isRtl ? '1' : '0' }}">
        @include('layouts.partials.figma-module-header', [
            'compact' => true,
            'title' => __('inspection_visits.list'),
            'crumbs' => [
                ['label' => __('dashboard.modules')],
                ['label' => __('dashboard.nav.corporate_communication')],
                ['label' => __('inspection_visits.list')],
            ],
        ])

        <div class="fm-hero fm-hero--split fm-inq-toolbar">
            <div class="cc-hero__main">
                <span class="cc-hero__icon" aria-hidden="true"><i class="bi bi-briefcase"></i></span>
                <div class="fm-hero__copy">
                    <h1>{{ __('inspection_visits.list') }}</h1>
                    <p>{{ __('inspection_visits.list_subtitle') }}</p>
                </div>
            </div>
            <a href="{{ route('modules.inspection-visits.create') }}" class="fm-btn--cta">
                <img src="{{ asset('images/figma/inquiries/plus.svg') }}" alt="" width="18" height="18">
                {{ __('inspection_visits.create') }}
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="iv-counters" aria-labelledby="ivCountersTitle">
            <h2 id="ivCountersTitle" class="visually-hidden">{{ __('inspection_visits.counters.title') }}</h2>
            <div class="iv-counter-grid">
                @foreach ($statusCounters as $index => $counter)
                    @php
                        $tone = ['blue', 'green', 'amber', 'violet', 'teal', 'sky', 'rose'][$index % 7];
                        $icons = [
                            'bi-file-earmark-plus',
                            'bi-reply',
                            'bi-hourglass-split',
                            'bi-arrow-up-right-circle',
                            'bi-building',
                            'bi-send-check',
                            'bi-arrow-return-left',
                        ];
                        $icon = $icons[$index % count($icons)];
                    @endphp
                    <a
                        href="{{ route('modules.inspection-visits.index', ['status' => $counter->status_id]) }}"
                        class="iv-counter iv-counter--{{ $tone }} {{ (string) $filters['status'] === (string) $counter->status_id ? 'is-active' : '' }}"
                    >
                        <div class="iv-counter__body">
                            <p class="iv-counter__label">{{ $counter->label }}</p>
                            <p class="iv-counter__value">{{ $counter->total }}</p>
                        </div>
                        <span class="iv-counter__icon" aria-hidden="true">
                            <i class="bi {{ $icon }}"></i>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="gc-panel mt-3" aria-labelledby="ivFiltersTitle">
            <h2 id="ivFiltersTitle" class="visually-hidden">{{ __('inspection_visits.filters.title') }}</h2>

            <form method="GET" action="{{ route('modules.inspection-visits.index') }}">
                <div class="gc-filter-grid iv-filter-grid">
                    <div class="gc-field">
                        <label for="ivFromDate">{{ __('inspection_visits.filters.from_date') }}</label>
                        <input id="ivFromDate" type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control">
                    </div>
                    <div class="gc-field">
                        <label for="ivToDate">{{ __('inspection_visits.filters.to_date') }}</label>
                        <input id="ivToDate" type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control">
                    </div>
                    <div class="gc-field">
                        <label for="ivVisitType">{{ __('inspection_visits.filters.visit_type') }}</label>
                        <select id="ivVisitType" name="visit_type" class="form-select">
                            <option value="">{{ __('inspection_visits.filters.visit_type_all') }}</option>
                            @foreach ($visitTypeOptions as $type)
                                <option value="{{ $type->id }}" @selected((string) $filters['visit_type'] === (string) $type->id)>
                                    {{ $type->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-field">
                        <label for="ivAuthority">{{ __('inspection_visits.filters.authority') }}</label>
                        <select id="ivAuthority" name="authority" class="form-select">
                            <option value="">{{ __('inspection_visits.filters.authority_all') }}</option>
                            @foreach ($authorityOptions as $authority)
                                <option value="{{ $authority->id }}" @selected((string) $filters['authority'] === (string) $authority->id)>
                                    {{ $authority->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-field">
                        <label for="ivBranch">{{ __('inspection_visits.filters.branch') }}</label>
                        <select id="ivBranch" name="branch" class="form-select">
                            <option value="">{{ __('inspection_visits.filters.branch_all') }}</option>
                            @foreach ($branchOptions as $branch)
                                <option value="{{ $branch->id }}" @selected((string) $filters['branch'] === (string) $branch->id)>
                                    {{ $branch->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-field">
                        <label for="ivSection">{{ __('inspection_visits.filters.section') }}</label>
                        <select id="ivSection" name="section" class="form-select">
                            <option value="">{{ __('inspection_visits.filters.section_all') }}</option>
                            @foreach ($sectionOptions as $section)
                                <option value="{{ $section->id }}" @selected((string) $filters['section'] === (string) $section->id)>
                                    {{ $section->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-field">
                        <label for="ivStatus">{{ __('inspection_visits.filters.status') }}</label>
                        <select id="ivStatus" name="status" class="form-select">
                            <option value="">{{ __('inspection_visits.filters.status_all') }}</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status->id }}" @selected((string) $filters['status'] === (string) $status->id)>
                                    {{ $status->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-filter-actions">
                        <button type="submit" class="btn btn-primary">{{ __('inspection_visits.filters.search') }}</button>
                        @if ($hasFilters)
                            <a href="{{ route('modules.inspection-visits.index') }}" class="btn btn-outline-secondary">
                                {{ __('inspection_visits.filters.reset') }}
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="gc-table-wrap">
                @include('layouts.partials.corporate-list-head', ['title' => __('inspection_visits.list'), 'count' => $visits->total(), 'countLabel' => __('inspection_visits.counters.title')])
                @if ($visits->isEmpty())
                    <div class="gc-empty">
                        {{ $hasFilters ? __('inspection_visits.table.empty_filtered') : __('inspection_visits.table.empty') }}
                    </div>
                @else
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('inspection_visits.table.visit_number') }}</th>
                                <th>{{ __('inspection_visits.table.visit_date') }}</th>
                                <th>{{ __('inspection_visits.table.visit_type') }}</th>
                                <th>{{ __('inspection_visits.table.authority') }}</th>
                                <th>{{ __('inspection_visits.table.branch') }}</th>
                                <th>{{ __('inspection_visits.table.section') }}</th>
                                <th>{{ __('inspection_visits.table.subject') }}</th>
                                <th>{{ __('inspection_visits.table.status') }}</th>
                                <th class="gc-col-actions">{{ __('inspection_visits.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visits as $visit)
                                <tr>
                                    <td class="gc-col-date">
                                        <a href="{{ route('modules.inspection-visits.show', $visit->id) }}" class="gc-link-count">
                                            {{ $visit->displayNumber() }}
                                        </a>
                                    </td>
                                    <td class="gc-col-date">{{ optional($visit->visit_date)->format('Y-m-d H:i') ?: '—' }}</td>
                                    <td>
                                        <span class="gc-cell-clip" title="{{ $visit->visitType?->localizedName() }}">
                                            {{ $visit->visitType?->localizedName() ?: '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="gc-cell-clip" title="{{ $visit->authority?->localizedName() }}">
                                            {{ $visit->authority?->localizedName() ?: '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="gc-cell-clip" title="{{ $visit->branch?->localizedName() }}">
                                            {{ $visit->branch?->localizedName() ?: '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="gc-cell-clip" title="{{ $visit->section?->localizedName() }}">
                                            {{ $visit->section?->localizedName() ?: '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="gc-cell-clip" title="{{ $visit->visitNumberRecord?->subject }}">
                                            {{ $visit->visitNumberRecord?->subject ?: '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="gc-badge" style="background-color: {{ $visit->currentStatus?->badgeColor() ?: '#64748b' }};">
                                            {{ $visit->currentStatus?->localizedName() ?: __('inspection_visits.status_unknown') }}
                                        </span>
                                    </td>
                                    <td class="gc-col-actions">
                                        <div class="gc-actions" data-gc-actions>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary gc-actions__toggle"
                                                aria-expanded="false"
                                                aria-haspopup="menu"
                                                aria-label="{{ __('inspection_visits.actions.menu') }}"
                                            >
                                                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                            </button>
                                            <div class="gc-actions__menu" role="menu" hidden>
                                                <a class="gc-actions__item" role="menuitem" href="{{ route('modules.inspection-visits.show', $visit->id) }}">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                                    <span>{{ __('inspection_visits.actions.view') }}</span>
                                                </a>
                                                <a class="gc-actions__item" role="menuitem" href="{{ route('modules.inspection-visits.receipt', $visit->id) }}">
                                                    <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                                                    <span>{{ __('inspection_visits.actions.receipt') }}</span>
                                                </a>
                                                <a class="gc-actions__item" role="menuitem" href="{{ route('modules.inspection-visits.pdf', $visit->id) }}">
                                                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                                                    <span>PDF</span>
                                                </a>
                                                <a class="gc-actions__item" role="menuitem" href="{{ route('modules.inspection-visits.show', $visit->id) }}#status-update">
                                                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                                    <span>{{ __('inspection_visits.actions.update_status') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $visits->links('pagination.hm') }}
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var root = document.querySelector('.hm-iv');
            if (!root) return;

            var openMenu = null;
            var isRtl = root.getAttribute('data-gc-rtl') === '1';

            function closeMenu() {
                if (!openMenu) return;

                var toggle = openMenu._gcToggle;
                var wrap = openMenu._gcWrap;

                openMenu.classList.remove('is-open');
                openMenu.hidden = true;
                openMenu.removeAttribute('style');

                if (wrap && openMenu.parentElement !== wrap) {
                    wrap.appendChild(openMenu);
                }

                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.classList.remove('is-open');
                }

                openMenu._gcToggle = null;
                openMenu._gcWrap = null;
                openMenu = null;
            }

            function placeMenu(toggle, menu) {
                if (menu.parentElement !== document.body) {
                    document.body.appendChild(menu);
                }

                menu.hidden = false;
                menu.classList.add('is-open');

                var rect = toggle.getBoundingClientRect();
                var menuWidth = menu.offsetWidth || 240;
                var menuHeight = menu.offsetHeight || 160;
                var gap = 6;
                var pad = 8;

                var top = rect.bottom + gap;
                if (top + menuHeight > window.innerHeight - pad) {
                    top = rect.top - menuHeight - gap;
                }
                top = Math.max(pad, Math.min(top, window.innerHeight - menuHeight - pad));

                var left = isRtl ? rect.left : (rect.right - menuWidth);
                left = Math.max(pad, Math.min(left, window.innerWidth - menuWidth - pad));

                menu.style.top = Math.round(top) + 'px';
                menu.style.left = Math.round(left) + 'px';
                menu.style.right = 'auto';
                menu.style.bottom = 'auto';
            }

            root.addEventListener('click', function (event) {
                var toggle = event.target.closest('.gc-actions__toggle');
                if (!toggle) return;

                event.preventDefault();
                event.stopPropagation();

                var wrap = toggle.closest('[data-gc-actions]');
                var menu = wrap ? wrap.querySelector('.gc-actions__menu') : null;
                if (!menu) return;

                if (openMenu === menu) {
                    closeMenu();
                    return;
                }

                closeMenu();

                menu._gcToggle = toggle;
                menu._gcWrap = wrap;
                toggle.setAttribute('aria-expanded', 'true');
                toggle.classList.add('is-open');
                placeMenu(toggle, menu);
                openMenu = menu;

                requestAnimationFrame(function () {
                    if (openMenu === menu) {
                        placeMenu(toggle, menu);
                    }
                });
            });

            document.addEventListener('click', function (event) {
                if (!openMenu) return;
                if (event.target.closest('.gc-actions__menu') || event.target.closest('.gc-actions__toggle')) {
                    return;
                }
                closeMenu();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });

            window.addEventListener('resize', closeMenu);
            window.addEventListener('scroll', closeMenu, true);
        })();
    </script>
@endpush
