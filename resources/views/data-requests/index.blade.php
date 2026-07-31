@extends('layouts.app')

@section('title', __('data_requests.list'))
@section('sidebar_heading', __('data_requests.title'))
@section('sidebar_subheading', __('data_requests.subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inspection-visits.css') }}?v={{ filemtime(public_path('css/hm-inspection-visits.css')) }}" rel="stylesheet">
@endpush

@section('content')
    @php $isRtl = app()->getLocale() === 'ar'; @endphp
    <div class="hm-gc hm-iv {{ $isRtl ? 'hm-gc--rtl' : 'hm-gc--ltr' }}" data-gc-rtl="{{ $isRtl ? '1' : '0' }}">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <span>{{ __('corporate_communication.title') }}</span>
            <span>/</span>
            <span class="is-chip">{{ __('data_requests.list') }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('data_requests.list') }}</h1>
                <p>{{ __('data_requests.list_subtitle') }}</p>
            </div>
            <a href="{{ route('modules.data-requests.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                {{ __('data_requests.create') }}
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="iv-counters" aria-labelledby="drCountersTitle">
            <h2 id="drCountersTitle" class="visually-hidden">{{ __('data_requests.counters.title') }}</h2>
            <div class="iv-counter-grid">
                @foreach ($statusCounters as $index => $counter)
                    @php
                        $tone = ['violet', 'rose', 'sky', 'teal', 'blue', 'amber', 'green', 'orange'][$index % 8];
                        $icons = [
                            'bi-arrow-up-right-circle',
                            'bi-arrow-return-left',
                            'bi-send-check',
                            'bi-building',
                            'bi-inbox',
                            'bi-x-circle',
                            'bi-check2-circle',
                            'bi-hourglass-split',
                        ];
                        $icon = $icons[$index % count($icons)];
                    @endphp
                    <a
                        href="{{ route('modules.data-requests.index', ['status' => $counter->status_id]) }}"
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

        <section class="gc-panel mt-3">
            <form method="GET" action="{{ route('modules.data-requests.index') }}">
                <div class="gc-filter-grid iv-filter-grid">
                    <div class="gc-field">
                        <label for="drFromDate">{{ __('data_requests.filters.from_date') }}</label>
                        <input id="drFromDate" type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control">
                    </div>
                    <div class="gc-field">
                        <label for="drToDate">{{ __('data_requests.filters.to_date') }}</label>
                        <input id="drToDate" type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control">
                    </div>
                    <div class="gc-field">
                        <label for="drEntity">{{ __('data_requests.filters.entity') }}</label>
                        <select id="drEntity" name="entity" class="form-select">
                            <option value="">{{ __('data_requests.filters.entity_all') }}</option>
                            @foreach ($entityOptions as $entity)
                                <option value="{{ $entity->id }}" @selected((string) $filters['entity'] === (string) $entity->id)>
                                    {{ $entity->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-field">
                        <label for="drBranch">{{ __('data_requests.filters.branch') }}</label>
                        <select id="drBranch" name="branch" class="form-select">
                            <option value="">{{ __('data_requests.filters.branch_all') }}</option>
                            @foreach ($branchOptions as $branch)
                                <option value="{{ $branch->id }}" @selected((string) $filters['branch'] === (string) $branch->id)>
                                    {{ $branch->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-field">
                        <label for="drSection">{{ __('data_requests.filters.section') }}</label>
                        <select id="drSection" name="section" class="form-select">
                            <option value="">{{ __('data_requests.filters.section_all') }}</option>
                            @foreach ($sectionOptions as $section)
                                <option value="{{ $section->id }}" @selected((string) $filters['section'] === (string) $section->id)>
                                    {{ $section->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-field">
                        <label for="drStatus">{{ __('data_requests.filters.status') }}</label>
                        <select id="drStatus" name="status" class="form-select">
                            <option value="">{{ __('data_requests.filters.status_all') }}</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status->id }}" @selected((string) $filters['status'] === (string) $status->id)>
                                    {{ $status->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-filter-actions">
                        <button type="submit" class="btn btn-primary">{{ __('data_requests.filters.search') }}</button>
                        @if ($hasFilters)
                            <a href="{{ route('modules.data-requests.index') }}" class="btn btn-outline-secondary">
                                {{ __('data_requests.filters.reset') }}
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="gc-table-wrap">
                @if ($requests->isEmpty())
                    <div class="gc-empty">
                        {{ $hasFilters ? __('data_requests.table.empty_filtered') : __('data_requests.table.empty') }}
                    </div>
                @else
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('data_requests.table.request_number') }}</th>
                                <th>{{ __('data_requests.table.date') }}</th>
                                <th>{{ __('data_requests.table.entity') }}</th>
                                <th>{{ __('data_requests.table.section_branch') }}</th>
                                <th>{{ __('data_requests.table.subject') }}</th>
                                <th>{{ __('data_requests.table.status') }}</th>
                                <th class="gc-col-actions">{{ __('data_requests.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $item)
                                <tr>
                                    <td class="gc-col-date">
                                        <a href="{{ route('modules.data-requests.show', $item->id) }}" class="gc-link-count">
                                            {{ $item->displayNumber() }}
                                        </a>
                                    </td>
                                    <td class="gc-col-date">{{ $item->date ?: '—' }}</td>
                                    <td>
                                        <span class="gc-cell-clip" title="{{ $item->entity?->localizedName() }}">
                                            {{ $item->entity?->localizedName() ?: '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="gc-cell-clip" title="{{ $item->section?->localizedName() }} / {{ $item->branch?->localizedName() }}">
                                            {{ $item->section?->localizedName() ?: '—' }}
                                            <span class="text-muted">/</span>
                                            {{ $item->branch?->localizedName() ?: '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="gc-cell-clip" title="{{ $item->subject() }}">
                                            {{ $item->subject() ?: '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="gc-badge" style="background-color: {{ $item->currentStatus?->badgeColor() ?: '#64748b' }};">
                                            {{ $item->currentStatus?->localizedName() ?: __('data_requests.status_unknown') }}
                                        </span>
                                    </td>
                                    <td class="gc-col-actions">
                                        <div class="gc-actions" data-gc-actions>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary gc-actions__toggle"
                                                aria-expanded="false"
                                                aria-haspopup="menu"
                                                aria-label="{{ __('data_requests.actions.menu') }}"
                                            >
                                                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                            </button>
                                            <div class="gc-actions__menu" role="menu" hidden>
                                                <a class="gc-actions__item" role="menuitem" href="{{ route('modules.data-requests.show', $item->id) }}">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                                    <span>{{ __('data_requests.actions.view') }}</span>
                                                </a>
                                                <a class="gc-actions__item" role="menuitem" href="{{ route('modules.data-requests.receipt', $item->id) }}">
                                                    <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                                                    <span>{{ __('data_requests.actions.receipt') }}</span>
                                                </a>
                                                <a class="gc-actions__item" role="menuitem" href="{{ route('modules.data-requests.show', $item->id) }}#status-update">
                                                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                                    <span>{{ __('data_requests.actions.update_status') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $requests->links('pagination.hm') }}
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
