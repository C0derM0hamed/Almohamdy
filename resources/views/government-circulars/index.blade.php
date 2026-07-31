@extends('layouts.app')

@section('title', __('government_circulars.list'))
@section('sidebar_heading', __('government_circulars.title'))
@section('sidebar_subheading', __('government_circulars.subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
@endpush

@section('content')
    @php $isRtl = app()->getLocale() === 'ar'; @endphp
    <div class="hm-gc {{ $isRtl ? 'hm-gc--rtl' : 'hm-gc--ltr' }}" data-gc-rtl="{{ $isRtl ? '1' : '0' }}">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <span>{{ __('corporate_communication.title') }}</span>
            <span>/</span>
            <span class="is-chip">{{ __('government_circulars.list') }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('government_circulars.list') }}</h1>
                <p>{{ __('government_circulars.list_subtitle') }}</p>
            </div>
            <a href="{{ route('modules.government-circulars.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                {{ __('government_circulars.create') }}
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="gc-panel gc-panel--flat" aria-labelledby="gcFiltersTitle">
            <h2 id="gcFiltersTitle" class="visually-hidden">{{ __('government_circulars.filters.title') }}</h2>

            <form method="GET" action="{{ route('modules.government-circulars.index') }}">
                <div class="gc-filter-grid">
                    <div class="gc-field">
                        <label for="gcAuthority">{{ __('government_circulars.filters.authority') }}</label>
                        <select id="gcAuthority" name="authority" class="form-select">
                            <option value="">{{ __('government_circulars.filters.authority_all') }}</option>
                            @foreach ($authorityOptions as $authority)
                                <option value="{{ $authority->id }}" @selected((string) $filters['authority'] === (string) $authority->id)>
                                    {{ $authority->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="gc-field">
                        <label for="gcSection">{{ __('government_circulars.filters.section') }}</label>
                        <select id="gcSection" name="section" class="form-select">
                            <option value="">{{ __('government_circulars.filters.section_all') }}</option>
                            @foreach ($sectionOptions as $section)
                                <option value="{{ $section->id }}" @selected((string) $filters['section'] === (string) $section->id)>
                                    {{ $section->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="gc-field">
                        <label for="gcBranch">{{ __('government_circulars.filters.branch') }}</label>
                        <select id="gcBranch" name="branch" class="form-select">
                            <option value="">{{ __('government_circulars.filters.branch_all') }}</option>
                            @foreach ($branchOptions as $branch)
                                <option value="{{ $branch->id }}" @selected((string) $filters['branch'] === (string) $branch->id)>
                                    {{ $branch->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="gc-field">
                        <label for="gcFromDate">{{ __('government_circulars.filters.from_date') }}</label>
                        <input id="gcFromDate" type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control">
                    </div>

                    <div class="gc-field">
                        <label for="gcToDate">{{ __('government_circulars.filters.to_date') }}</label>
                        <input id="gcToDate" type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control">
                    </div>

                    <div class="gc-field">
                        <label for="gcSubject">{{ __('government_circulars.filters.subject') }}</label>
                        <input
                            id="gcSubject"
                            type="text"
                            name="subject"
                            value="{{ $filters['subject'] }}"
                            class="form-control"
                            placeholder="{{ __('government_circulars.filters.subject_placeholder') }}"
                        >
                    </div>

                    <div class="gc-filter-actions">
                        <button type="submit" class="btn btn-primary">{{ __('government_circulars.filters.search') }}</button>
                        @if ($hasFilters)
                            <a href="{{ route('modules.government-circulars.index') }}" class="btn btn-outline-secondary">
                                {{ __('government_circulars.filters.reset') }}
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="gc-table-wrap">
                @if ($circulars->isEmpty())
                    <div class="gc-empty">
                        {{ $hasFilters ? __('government_circulars.table.empty_filtered') : __('government_circulars.table.empty') }}
                    </div>
                @else
                    <table class="gc-table gc-table--list">
                        <thead>
                            <tr>
                                <th>{{ __('government_circulars.table.sent_date') }}</th>
                                <th>{{ __('government_circulars.table.authority') }}</th>
                                <th>{{ __('government_circulars.table.classification') }}</th>
                                <th>{{ __('government_circulars.table.subject') }}</th>
                                <th>{{ __('government_circulars.table.department') }}</th>
                                <th>{{ __('government_circulars.table.status') }}</th>
                                <th>{{ __('government_circulars.table.recipients') }}</th>
                                <th class="gc-col-actions">{{ __('government_circulars.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($circulars as $circular)
                                <tr>
                                    <td class="gc-col-date">{{ optional($circular->issue_date)->format('Y-m-d') ?: '—' }}</td>
                                    <td>{{ $circular->authority?->localizedName() ?: '—' }}</td>
                                    <td>{{ $circular->classification?->localizedName() ?: '—' }}</td>
                                    <td>{{ $circular->subject ?: '—' }}</td>
                                    <td>
                                        <a href="{{ route('modules.government-circulars.departments', $circular->id) }}" class="gc-link-count">
                                            {{ $circular->section?->localizedName() ?: '—' }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="gc-badge" style="background-color: {{ $circular->currentStatus?->badgeColor() ?? '#64748b' }};">
                                            {{ $circular->currentStatus?->localizedName() ?: __('government_circulars.status_unknown') }}
                                        </span>
                                    </td>
                                    <td class="gc-col-count">
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
                                    <td class="gc-col-actions">
                                        <div class="gc-actions" data-gc-actions>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary gc-actions__toggle"
                                                aria-expanded="false"
                                                aria-haspopup="menu"
                                                aria-label="{{ __('government_circulars.actions.menu') }}"
                                            >
                                                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
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

                    <div class="mt-3">
                        {{ $circulars->links('pagination.hm') }}
                    </div>
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
        })();
    </script>
@endpush
