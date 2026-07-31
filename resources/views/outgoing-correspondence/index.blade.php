@extends('layouts.app')

@section('title', __('outgoing_correspondence.list'))
@section('sidebar_heading', __('outgoing_correspondence.title'))
@section('sidebar_subheading', __('outgoing_correspondence.subtitle'))

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
            <span class="is-chip">{{ __('outgoing_correspondence.list') }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('outgoing_correspondence.list') }}</h1>
                <p>{{ __('outgoing_correspondence.list_subtitle') }}</p>
            </div>
            <a href="{{ route('modules.outgoing-correspondence.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                {{ __('outgoing_correspondence.create') }}
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="iv-counters" aria-labelledby="olCountersTitle">
            <h2 id="olCountersTitle" class="visually-hidden">{{ __('outgoing_correspondence.counters.title') }}</h2>
            <div class="iv-counter-grid">
                @foreach ($statusCounters as $index => $counter)
                    @php
                        $tone = ['violet', 'rose', 'sky', 'teal', 'blue', 'amber', 'green', 'orange'][$index % 8];
                        $icons = [
                            'bi-file-earmark-plus',
                            'bi-send-check',
                            'bi-reply',
                            'bi-check2-circle',
                            'bi-arrow-return-left',
                            'bi-inbox',
                            'bi-truck',
                            'bi-building-check',
                            'bi-arrow-counterclockwise',
                            'bi-person-check',
                            'bi-chat-left-text',
                            'bi-files',
                        ];
                        $icon = $icons[$index % count($icons)];
                    @endphp
                    <a
                        href="{{ route('modules.outgoing-correspondence.index', ['status' => $counter->status_id]) }}"
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
            <form method="GET" action="{{ route('modules.outgoing-correspondence.index') }}">
                <div class="gc-filter-grid iv-filter-grid">
                    <div class="gc-field">
                        <label for="olFromDate">{{ __('outgoing_correspondence.filters.from_date') }}</label>
                        <input id="olFromDate" type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control">
                    </div>
                    <div class="gc-field">
                        <label for="olToDate">{{ __('outgoing_correspondence.filters.to_date') }}</label>
                        <input id="olToDate" type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control">
                    </div>
                    <div class="gc-field">
                        <label for="olSector">{{ __('outgoing_correspondence.filters.sector') }}</label>
                        <select id="olSector" name="sector" class="form-select">
                            <option value="">{{ __('outgoing_correspondence.filters.sector_all') }}</option>
                            @foreach ($sectorOptions as $sector)
                                <option value="{{ $sector->id }}" @selected((string) $filters['sector'] === (string) $sector->id)>
                                    {{ $sector->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-field">
                        <label for="olAuthority">{{ __('outgoing_correspondence.filters.authority') }}</label>
                        <select id="olAuthority" name="authority" class="form-select">
                            <option value="">{{ __('outgoing_correspondence.filters.authority_all') }}</option>
                            @foreach ($authorityOptions as $authority)
                                <option value="{{ $authority->id }}" @selected((string) $filters['authority'] === (string) $authority->id)>
                                    {{ $authority->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-field">
                        <label for="olBranch">{{ __('outgoing_correspondence.filters.branch') }}</label>
                        <select id="olBranch" name="branch" class="form-select">
                            <option value="">{{ __('outgoing_correspondence.filters.branch_all') }}</option>
                            @foreach ($branchOptions as $branch)
                                <option value="{{ $branch->id }}" @selected((string) $filters['branch'] === (string) $branch->id)>
                                    {{ $branch->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-field">
                        <label for="olStatus">{{ __('outgoing_correspondence.filters.status') }}</label>
                        <select id="olStatus" name="status" class="form-select">
                            <option value="">{{ __('outgoing_correspondence.filters.status_all') }}</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status->id }}" @selected((string) $filters['status'] === (string) $status->id)>
                                    {{ $status->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gc-field">
                        <label for="olSubject">{{ __('outgoing_correspondence.filters.subject') }}</label>
                        <input id="olSubject" type="text" name="subject" value="{{ $filters['subject'] }}" class="form-control" placeholder="{{ __('outgoing_correspondence.filters.subject_placeholder') }}">
                    </div>
                    <div class="gc-filter-actions">
                        <button type="submit" class="btn btn-primary">{{ __('outgoing_correspondence.filters.search') }}</button>
                        @if ($hasFilters)
                            <a href="{{ route('modules.outgoing-correspondence.index') }}" class="btn btn-outline-secondary">
                                {{ __('outgoing_correspondence.filters.reset') }}
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="gc-table-wrap">
                @if ($items->isEmpty())
                    <div class="gc-empty">
                        {{ $hasFilters ? __('outgoing_correspondence.table.empty_filtered') : __('outgoing_correspondence.table.empty') }}
                    </div>
                @else
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('outgoing_correspondence.table.number') }}</th>
                                <th>{{ __('outgoing_correspondence.table.date') }}</th>
                                <th>{{ __('outgoing_correspondence.table.authority') }}</th>
                                <th>{{ __('outgoing_correspondence.table.branch') }}</th>
                                <th>{{ __('outgoing_correspondence.table.subject') }}</th>
                                <th>{{ __('outgoing_correspondence.table.status') }}</th>
                                <th class="gc-col-actions">{{ __('outgoing_correspondence.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td class="gc-col-date">
                                        <a href="{{ route('modules.outgoing-correspondence.show', $item->id) }}" class="gc-link-count">
                                            {{ $item->displayNumber() }}
                                        </a>
                                    </td>
                                    <td class="gc-col-date">
                                        {{ $item->issue_date?->format('Y-m-d H:i') ?: '—' }}
                                    </td>
                                    <td>
                                        <span class="gc-cell-clip" title="{{ $item->authority?->localizedName() }}">
                                            {{ $item->authority?->localizedName() ?: '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="gc-cell-clip" title="{{ $item->branch?->localizedName() }}">
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
                                            {{ $item->currentStatus?->localizedName() ?: __('outgoing_correspondence.status_unknown') }}
                                        </span>
                                    </td>
                                    <td class="gc-col-actions">
                                        <div class="gc-actions" data-gc-actions>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary gc-actions__toggle"
                                                aria-expanded="false"
                                                aria-haspopup="menu"
                                                aria-label="{{ __('outgoing_correspondence.actions.menu') }}"
                                            >
                                                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                            </button>
                                            <div class="gc-actions__menu" role="menu" hidden>
                                                <a class="gc-actions__item" role="menuitem" href="{{ route('modules.outgoing-correspondence.show', $item->id) }}">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                                    <span>{{ __('outgoing_correspondence.actions.view') }}</span>
                                                </a>
                                                <a class="gc-actions__item" role="menuitem" href="{{ route('modules.outgoing-correspondence.show', $item->id) }}#status-update">
                                                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                                    <span>{{ __('outgoing_correspondence.actions.update_status') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $items->links('pagination.hm') }}
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
            });

            document.addEventListener('click', function (event) {
                if (!openMenu) return;
                if (event.target.closest('.gc-actions__menu') || event.target.closest('.gc-actions__toggle')) {
                    return;
                }
                closeMenu();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeMenu();
            });

            window.addEventListener('resize', closeMenu);
            window.addEventListener('scroll', closeMenu, true);
        })();
    </script>
@endpush
