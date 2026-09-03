@extends('layouts.app')

@section('title', __('government_circulars.list'))
@section('sidebar_heading', __('government_circulars.title'))
@section('sidebar_subheading', __('government_circulars.subtitle'))
@section('figma_page_header', true)

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
@endpush

@section('content')
    @php $isRtl = app()->getLocale() === 'ar'; @endphp
    <div class="hm-fm hm-gc hm-gc--list {{ $isRtl ? 'hm-gc--rtl' : 'hm-gc--ltr' }}" data-gc-rtl="{{ $isRtl ? '1' : '0' }}">
        @include('layouts.partials.figma-module-header', [
            'compact' => true,
            'title' => __('government_circulars.list'),
            'crumbs' => [
                ['label' => __('dashboard.modules')],
                ['label' => __('dashboard.nav.corporate_communication')],
                ['label' => __('government_circulars.list')],
            ],
        ])

        <div class="fm-hero fm-hero--split fm-inq-toolbar">
            <div class="fm-hero__copy">
                <h1>{{ __('government_circulars.list') }}</h1>
                <p>{{ __('government_circulars.list_subtitle') }}</p>
            </div>
            <a href="{{ route('modules.government-circulars.create') }}" class="fm-btn--cta">
                <img src="{{ asset('images/figma/inquiries/plus.svg') }}" alt="" width="18" height="18">
                {{ __('government_circulars.create') }}
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="fm-search" aria-labelledby="gcFiltersTitle">
            <div class="fm-search__head">
                <h2 id="gcFiltersTitle">{{ __('doctors_directory.filters_section_title') }}</h2>
            </div>
            <form method="GET" action="{{ route('modules.government-circulars.index') }}" class="gc-filter-form">
                <div class="gc-filter-row">
                    <div class="fm-field">
                        <label for="gcSection">{{ __('government_circulars.filters.section') }}</label>
                        <select id="gcSection" name="section" class="fm-input">
                            <option value="">{{ __('government_circulars.filters.section_all') }}</option>
                            @foreach ($sectionOptions as $section)
                                <option value="{{ $section->id }}" @selected((string) $filters['section'] === (string) $section->id)>
                                    {{ $section->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fm-field">
                        <label for="gcAuthority">{{ __('government_circulars.filters.authority') }}</label>
                        <select id="gcAuthority" name="authority" class="fm-input">
                            <option value="">{{ __('government_circulars.filters.authority_all') }}</option>
                            @foreach ($authorityOptions as $authority)
                                <option value="{{ $authority->id }}" @selected((string) $filters['authority'] === (string) $authority->id)>
                                    {{ $authority->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fm-field">
                        <label for="gcToDate">{{ __('government_circulars.filters.to_date') }}</label>
                        <input id="gcToDate" class="fm-input" type="date" name="to_date" value="{{ $filters['to_date'] }}">
                    </div>
                    <div class="fm-field">
                        <label for="gcFromDate">{{ __('government_circulars.filters.from_date') }}</label>
                        <input id="gcFromDate" class="fm-input" type="date" name="from_date" value="{{ $filters['from_date'] }}">
                    </div>
                </div>
                <div class="gc-filter-row gc-filter-row--actions">
                    <div class="fm-field">
                        <label for="gcBranch">{{ __('government_circulars.filters.branch') }}</label>
                        <select id="gcBranch" name="branch" class="fm-input">
                            <option value="">{{ __('government_circulars.filters.branch_all') }}</option>
                            @foreach ($branchOptions as $branch)
                                <option value="{{ $branch->id }}" @selected((string) $filters['branch'] === (string) $branch->id)>
                                    {{ $branch->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fm-field">
                        <label for="gcSubject">{{ __('government_circulars.filters.subject') }}</label>
                        <input
                            id="gcSubject"
                            class="fm-input"
                            type="text"
                            name="subject"
                            value="{{ $filters['subject'] }}"
                            placeholder="{{ __('government_circulars.filters.subject_placeholder') }}"
                        >
                    </div>
                    <button type="submit" class="fm-btn--search">{{ __('government_circulars.filters.search') }}</button>
                    @if ($hasFilters)
                        <a href="{{ route('modules.government-circulars.index') }}" class="fm-btn--reset">
                            {{ __('government_circulars.filters.reset') }}
                            <img src="{{ asset('images/figma/doctors/reset.svg') }}" alt="" width="18" height="18">
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <section class="fm-section">
            <div class="fm-table-wrap">
                <div class="fm-table-toolbar gc-toolbar">
                    @include('layouts.partials.figma.section-head', [
                        'title' => __('government_circulars.list'),
                        'countLabel' => __('government_circulars.circulars_count', ['count' => $circulars->total()]),
                    ])
                    <div class="gc-toolbar__tools">
                        <details class="gc-tool">
                            <summary class="gc-tool__btn" title="{{ __('government_circulars.toolbar.columns') }}" aria-label="{{ __('government_circulars.toolbar.columns') }}">
                                <img src="{{ asset('images/figma/circulars/columns.svg') }}" alt="" width="16" height="16">
                            </summary>
                            <div class="gc-tool__menu" data-gc-columns>
                                @foreach ([
                                    'date' => __('government_circulars.table.sent_date'),
                                    'authority' => __('government_circulars.table.authority'),
                                    'classification' => __('government_circulars.table.classification'),
                                    'subject' => __('government_circulars.table.subject'),
                                    'department' => __('government_circulars.table.department'),
                                    'status' => __('government_circulars.table.status'),
                                    'recipients' => __('government_circulars.table.recipients'),
                                    'actions' => __('government_circulars.table.actions'),
                                ] as $colKey => $colLabel)
                                    <label class="gc-tool__check">
                                        <input type="checkbox" value="{{ $colKey }}" checked>
                                        <span>{{ $colLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                        <button type="button" class="gc-tool__btn gc-tool__btn--label" data-gc-export>
                            <span>{{ __('government_circulars.toolbar.export') }}</span>
                            <img src="{{ asset('images/figma/circulars/export.svg') }}" alt="" width="16" height="16">
                        </button>
                        <a class="gc-tool__btn" href="{{ request()->fullUrl() }}" title="{{ __('government_circulars.toolbar.refresh') }}" aria-label="{{ __('government_circulars.toolbar.refresh') }}">
                            <img src="{{ asset('images/figma/circulars/refresh.svg') }}" alt="" width="16" height="16">
                        </a>
                        <details class="gc-tool">
                            <summary class="gc-tool__btn gc-tool__btn--label">
                                <span>{{ __('government_circulars.toolbar.sort') }}</span>
                                <img src="{{ asset('images/figma/circulars/sort.svg') }}" alt="" width="16" height="16">
                            </summary>
                            <div class="gc-tool__menu" data-gc-sort>
                                <button type="button" data-gc-sort-key="date">{{ __('government_circulars.toolbar.sort_date') }}</button>
                                <button type="button" data-gc-sort-key="subject">{{ __('government_circulars.toolbar.sort_subject') }}</button>
                                <button type="button" data-gc-sort-key="status">{{ __('government_circulars.toolbar.sort_status') }}</button>
                            </div>
                        </details>
                    </div>
                </div>

                @if ($circulars->isEmpty())
                    <div class="fm-empty">
                        {{ $hasFilters ? __('government_circulars.table.empty_filtered') : __('government_circulars.table.empty') }}
                    </div>
                @else
                    <div class="fm-table-scroll">
                        <table class="fm-table gc-table" data-gc-table>
                            <thead>
                                <tr>
                                    <th data-col="date">{{ __('government_circulars.table.sent_date') }}</th>
                                    <th data-col="authority">{{ __('government_circulars.table.authority') }}</th>
                                    <th data-col="classification">{{ __('government_circulars.table.classification') }}</th>
                                    <th data-col="subject">{{ __('government_circulars.table.subject') }}</th>
                                    <th data-col="department">{{ __('government_circulars.table.department') }}</th>
                                    <th data-col="status">{{ __('government_circulars.table.status') }}</th>
                                    <th data-col="recipients">{{ __('government_circulars.table.recipients') }}</th>
                                    <th class="gc-col-actions" data-col="actions">{{ __('government_circulars.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($circulars as $circular)
                                    @php
                                        $subject = $circular->subject ?: '—';
                                        $subjectInitial = $subject === '—' ? '—' : mb_substr(trim($subject), 0, 1);
                                        $issueDate = $circular->issue_date
                                            ? $circular->issue_date->locale(app()->getLocale())->translatedFormat('j F Y')
                                            : '—';
                                        $statusLabel = $circular->currentStatus?->localizedName() ?: __('government_circulars.status_unknown');
                                        $statusColor = $circular->currentStatus?->badgeColor() ?? '#64748b';
                                        $deptName = $circular->section?->localizedName() ?: '—';
                                    @endphp
                                    <tr
                                        data-sort-date="{{ optional($circular->issue_date)->format('Y-m-d') ?: '' }}"
                                        data-sort-subject="{{ $subject }}"
                                        data-sort-status="{{ $statusLabel }}"
                                    >
                                        <td data-col="date">
                                            <span class="gc-cell-icon">
                                                <img src="{{ asset('images/figma/inquiries/calendar.svg') }}" alt="" width="14" height="14">
                                                {{ $issueDate }}
                                            </span>
                                        </td>
                                        <td data-col="authority">
                                            <span class="gc-cell-icon">
                                                <img src="{{ asset('images/figma/locations/card-building.svg') }}" alt="" width="15" height="15">
                                                {{ $circular->authority?->localizedName() ?: '—' }}
                                            </span>
                                        </td>
                                        <td data-col="classification">
                                            @include('layouts.partials.figma.badge', [
                                                'label' => $circular->classification?->localizedName() ?: '—',
                                                'tone' => 'muted',
                                            ])
                                        </td>
                                        <td data-col="subject">
                                            <span class="fm-person">
                                                <span class="fm-avatar">{{ $subjectInitial }}</span>
                                                <span>{{ $subject }}</span>
                                            </span>
                                        </td>
                                        <td data-col="department">
                                            <a href="{{ route('modules.government-circulars.departments', $circular->id) }}" class="gc-dept-link">
                                                <img src="{{ asset('images/figma/locations/card-arrow.svg') }}" alt="" width="14" height="14">
                                                {{ $deptName }}
                                            </a>
                                        </td>
                                        <td data-col="status">
                                            <span class="gc-status-pill" style="--gc-status-color: {{ $statusColor }}">
                                                <span class="gc-status-pill__dot" aria-hidden="true"></span>
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td class="gc-col-count" data-col="recipients">
                                            <button
                                                type="button"
                                                class="gc-link-count"
                                                data-gc-receipt-modal
                                                data-gc-receipt-url="{{ route('modules.government-circulars.receipt', $circular->id) }}"
                                                data-gc-receipt-label="{{ $circular->displayNumber() }} — {{ $circular->subject }}"
                                            >
                                                {{ (int) ($circular->recipients_count ?? 0) }}
                                            </button>
                                        </td>
                                        <td class="gc-col-actions" data-col="actions">
                                            <div class="gc-actions" data-gc-actions>
                                                <a class="gc-row-view" href="{{ route('modules.government-circulars.show', $circular->id) }}">
                                                    {{ __('government_circulars.view_details') }}
                                                    <img src="{{ asset('images/figma/circulars/eye.svg') }}" alt="" width="15" height="15">
                                                </a>
                                                <button
                                                    type="button"
                                                    class="gc-actions__toggle"
                                                    aria-expanded="false"
                                                    aria-haspopup="menu"
                                                    aria-label="{{ __('government_circulars.actions.menu') }}"
                                                >
                                                    <img src="{{ asset('images/figma/circulars/more.svg') }}" alt="" width="16" height="16">
                                                </button>
                                                <div class="gc-actions__menu" role="menu" hidden>
                                                    <a class="gc-actions__item" role="menuitem" href="{{ route('modules.government-circulars.show', $circular->id) }}">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                                        <span>{{ __('government_circulars.actions.view') }}</span>
                                                    </a>
                                                    <button
                                                        type="button"
                                                        class="gc-actions__item"
                                                        role="menuitem"
                                                        data-gc-status-modal
                                                        data-gc-status-url="{{ route('modules.government-circulars.status', $circular->id) }}"
                                                        data-gc-status-current="{{ (int) $circular->status }}"
                                                        data-gc-status-label="{{ $circular->displayNumber() }}"
                                                    >
                                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                        <span>{{ __('government_circulars.actions.update_status') }}</span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="gc-actions__item"
                                                        role="menuitem"
                                                        data-gc-receipt-modal
                                                        data-gc-receipt-url="{{ route('modules.government-circulars.receipt', $circular->id) }}"
                                                        data-gc-receipt-label="{{ $circular->displayNumber() }} — {{ $circular->subject }}"
                                                    >
                                                        <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                                                        <span>{{ __('government_circulars.actions.receipt') }}</span>
                                                    </button>
                                                    <a class="gc-actions__item" role="menuitem" href="{{ route('modules.government-circulars.departments', $circular->id) }}">
                                                        <i class="bi bi-building" aria-hidden="true"></i>
                                                        <span>{{ __('government_circulars.actions.departments') }}</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @include('layouts.partials.figma.pagination', [
                        'paginator' => $circulars,
                        'summaryKey' => 'government_circulars.results_summary',
                    ])
                @endif
            </div>
        </section>
    </div>

    <div class="modal fade gc-status-modal" id="gcStatusModal" tabindex="-1" aria-labelledby="gcStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5 mb-0" id="gcStatusModalLabel">{{ __('government_circulars.status_form.title') }}</h2>
                        <p class="text-muted small mb-0 mt-1" id="gcStatusModalSubtitle">{{ __('government_circulars.status_form.subtitle') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('government_circulars.actions.cancel') }}"></button>
                </div>
                <form method="POST" action="#" id="gcStatusForm" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="gc-form-grid">
                            <div class="gc-field">
                                <label for="gc_modal_status_id">{{ __('government_circulars.status_form.status') }}</label>
                                <select id="gc_modal_status_id" name="status_id" class="form-select" required>
                                    <option value="">—</option>
                                </select>
                            </div>
                            <div class="gc-field gc-span-2">
                                <label for="gc_modal_details">{{ __('government_circulars.status_form.details') }}</label>
                                <textarea id="gc_modal_details" name="details" rows="3" class="form-control" maxlength="2000"></textarea>
                            </div>
                            <div class="gc-field gc-span-2">
                                <label for="gc_modal_attachment_file">{{ __('government_circulars.status_form.attachment') }}</label>
                                <input id="gc_modal_attachment_file" type="file" name="attachment_file" class="form-control">
                                <div class="form-text">{{ __('government_circulars.status_form.attachment_hint') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('government_circulars.actions.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('government_circulars.status_form.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade gc-receipt-modal" id="gcReceiptModal" tabindex="-1" aria-labelledby="gcReceiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5 mb-0" id="gcReceiptModalLabel">{{ __('government_circulars.receipt.title') }}</h2>
                        <p class="text-muted small mb-0 mt-1" id="gcReceiptModalSubtitle"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('government_circulars.actions.cancel') }}"></button>
                </div>
                <div class="modal-body hm-gc" id="gcReceiptModalBody">
                    <div class="gc-empty">{{ __('government_circulars.receipt.subtitle') }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" id="gcReceiptPrintBtn">{{ __('government_circulars.receipt.print') }}</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('government_circulars.actions.cancel') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var root = document.querySelector('.hm-gc');
            if (!root) return;

            var openMenu = null;
            var isRtl = root.getAttribute('data-gc-rtl') === '1';
            var statusOptions = @json(
                $statusOptions->map(fn ($status) => [
                    'id' => (int) $status->id,
                    'label' => $status->localizedName(),
                ])->values()
            );

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

            function openStatusModal(trigger) {
                var form = document.getElementById('gcStatusForm');
                var select = document.getElementById('gc_modal_status_id');
                var modalEl = document.getElementById('gcStatusModal');
                var subtitle = document.getElementById('gcStatusModalSubtitle');
                if (!form || !select || !modalEl) return;
                if (!window.bootstrap || !bootstrap.Modal) {
                    console.error('Bootstrap Modal is not available');
                    return;
                }

                // Escape layout transform/filter so fixed positioning is viewport-based.
                if (modalEl.parentElement !== document.body) {
                    document.body.appendChild(modalEl);
                }

                var url = trigger.getAttribute('data-gc-status-url') || '#';
                var current = parseInt(trigger.getAttribute('data-gc-status-current') || '0', 10);
                var label = trigger.getAttribute('data-gc-status-label') || '';

                form.setAttribute('action', url);
                form.reset();
                select.innerHTML = '<option value="">—</option>';
                (statusOptions || []).forEach(function (option) {
                    if (Number(option.id) === current) return;
                    var opt = document.createElement('option');
                    opt.value = String(option.id);
                    opt.textContent = option.label;
                    select.appendChild(opt);
                });

                if (subtitle) {
                    subtitle.textContent = label
                        ? @json(__('government_circulars.status_form.subtitle') . ' — ') + label
                        : @json(__('government_circulars.status_form.subtitle'));
                }

                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            function openReceiptModal(trigger) {
                var modalEl = document.getElementById('gcReceiptModal');
                var bodyEl = document.getElementById('gcReceiptModalBody');
                var subtitle = document.getElementById('gcReceiptModalSubtitle');
                if (!modalEl || !bodyEl || !window.bootstrap || !bootstrap.Modal) return;

                if (modalEl.parentElement !== document.body) {
                    document.body.appendChild(modalEl);
                }

                var url = trigger.getAttribute('data-gc-receipt-url') || '';
                var label = trigger.getAttribute('data-gc-receipt-label') || '';
                if (!url) return;

                if (subtitle) {
                    subtitle.textContent = label;
                }

                bodyEl.innerHTML = '<div class="gc-empty">…</div>';
                bootstrap.Modal.getOrCreateInstance(modalEl).show();

                var fetchUrl = url + (url.indexOf('?') === -1 ? '?' : '&') + 'modal=1';
                fetch(fetchUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                    },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Failed to load receipt');
                        return response.text();
                    })
                    .then(function (html) {
                        bodyEl.innerHTML = html;
                    })
                    .catch(function () {
                        bodyEl.innerHTML = '<div class="gc-empty text-danger">{{ __('government_circulars.receipt.empty') }}</div>';
                    });
            }

            var printBtn = document.getElementById('gcReceiptPrintBtn');
            if (printBtn) {
                printBtn.addEventListener('click', function () {
                    var bodyEl = document.getElementById('gcReceiptModalBody');
                    if (!bodyEl) return;
                    var win = window.open('', '_blank');
                    if (!win) return;
                    win.document.write(
                        '<!DOCTYPE html><html><head><title>' +
                        @json(__('government_circulars.receipt.title')) +
                        '</title><link rel="stylesheet" href="' +
                        @json(asset('css/hm-government-circulars.css')) +
                        '"><style>body{padding:1.5rem;font-family:system-ui,sans-serif}</style></head><body class="hm-gc">' +
                        bodyEl.innerHTML +
                        '</body></html>'
                    );
                    win.document.close();
                    win.focus();
                    setTimeout(function () {
                        win.print();
                    }, 300);
                });
            }

            // Menu is portaled to document.body, so listen on document (not only .hm-gc).
            document.addEventListener('click', function (event) {
                var receiptBtn = event.target.closest('[data-gc-receipt-modal]');
                if (receiptBtn) {
                    event.preventDefault();
                    event.stopPropagation();
                    closeMenu();
                    openReceiptModal(receiptBtn);
                    return;
                }

                var statusBtn = event.target.closest('[data-gc-status-modal]');
                if (statusBtn) {
                    event.preventDefault();
                    event.stopPropagation();
                    closeMenu();
                    openStatusModal(statusBtn);
                    return;
                }

                var toggle = event.target.closest('.gc-actions__toggle');
                if (toggle && root.contains(toggle)) {
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
                    return;
                }

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

            var table = root.querySelector('[data-gc-table]');
            var tbody = table ? table.querySelector('tbody') : null;

            root.querySelectorAll('[data-gc-columns] input[type="checkbox"]').forEach(function (box) {
                box.addEventListener('change', function () {
                    if (!table) return;
                    var key = box.value;
                    var hidden = !box.checked;
                    table.querySelectorAll('[data-col="' + key + '"]').forEach(function (cell) {
                        cell.hidden = hidden;
                    });
                });
            });

            var exportBtn = root.querySelector('[data-gc-export]');
            if (exportBtn) {
                exportBtn.addEventListener('click', function () {
                    var wrap = root.querySelector('.fm-table-scroll');
                    if (!wrap) return;
                    var win = window.open('', '_blank');
                    if (!win) return;
                    win.document.write(
                        '<!DOCTYPE html><html><head><title>' +
                        @json(__('government_circulars.list')) +
                        '</title><style>table{width:100%;border-collapse:collapse;font-family:sans-serif}th,td{border:1px solid #e9e8f1;padding:8px;text-align:start}</style></head><body>' +
                        wrap.innerHTML +
                        '</body></html>'
                    );
                    win.document.close();
                    win.focus();
                    setTimeout(function () { win.print(); }, 300);
                });
            }

            var sortDir = {};
            root.querySelectorAll('[data-gc-sort-key]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!tbody) return;
                    var key = btn.getAttribute('data-gc-sort-key');
                    sortDir[key] = sortDir[key] === 'asc' ? 'desc' : 'asc';
                    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
                    rows.sort(function (a, b) {
                        var av = (a.getAttribute('data-sort-' + key) || '').toString();
                        var bv = (b.getAttribute('data-sort-' + key) || '').toString();
                        var cmp = av.localeCompare(bv, undefined, { numeric: true, sensitivity: 'base' });
                        return sortDir[key] === 'asc' ? cmp : -cmp;
                    });
                    rows.forEach(function (row) { tbody.appendChild(row); });
                    var menu = btn.closest('details');
                    if (menu) menu.removeAttribute('open');
                });
            });
        })();
    </script>
@endpush
