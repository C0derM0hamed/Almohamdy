@extends('layouts.app')

@section('title', __('government_circulars.detail'))
@section('sidebar_heading', __('government_circulars.title'))
@section('sidebar_subheading', __('government_circulars.detail_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
@endpush

@section('content')
    @php
        $isRtl = app()->getLocale() === 'ar';
        $openStatusModal = $errors->any() || session('open_status_modal');
    @endphp
    <div class="hm-gc {{ $isRtl ? 'hm-gc--rtl' : 'hm-gc--ltr' }}" data-gc-rtl="{{ $isRtl ? '1' : '0' }}">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.government-circulars.index') }}">{{ __('government_circulars.list') }}</a>
            <span>/</span>
            <span class="is-chip">{{ $circular->displayNumber() }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('government_circulars.detail') }}</h1>
                <p>{{ $circular->subject }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="gc-badge" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>

                <div class="gc-actions" data-gc-actions>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary gc-actions__toggle"
                        aria-expanded="false"
                        aria-haspopup="menu"
                        aria-label="{{ __('government_circulars.actions.menu') }}"
                    >
                        <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                        <span>{{ __('government_circulars.actions.menu') }}</span>
                    </button>
                    <div class="gc-actions__menu" role="menu" hidden>
                        @if ($updatableStatuses->isNotEmpty())
                            <button
                                type="button"
                                class="gc-actions__item"
                                role="menuitem"
                                data-bs-toggle="modal"
                                data-bs-target="#gcStatusModal"
                            >
                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                <span>{{ __('government_circulars.actions.update_status') }}</span>
                            </button>
                        @endif
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
                            <span class="badge text-bg-light ms-auto">{{ $recipientsCount }}</span>
                        </button>
                        <a class="gc-actions__item" role="menuitem" href="{{ route('modules.government-circulars.departments', $circular->id) }}">
                            <i class="bi bi-building" aria-hidden="true"></i>
                            <span>{{ __('government_circulars.actions.departments') }}</span>
                            <span class="badge text-bg-light ms-auto">{{ $departmentsCount }}</span>
                        </a>
                        <a class="gc-actions__item" role="menuitem" href="{{ $formalPageUrl }}" target="_blank" rel="noopener">
                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                            <span>{{ __('government_circulars.formal.open_link') }}</span>
                        </a>
                        <a class="gc-actions__item" role="menuitem" href="{{ route('modules.government-circulars.index') }}">
                            <i class="bi bi-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
                            <span>{{ __('government_circulars.back_to_list') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="gc-panel">
            <div class="gc-detail-grid">
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('government_circulars.fields.authority') }}</span>
                    <span class="gc-detail-item__value">{{ $circular->authority?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('government_circulars.fields.classification') }}</span>
                    <span class="gc-detail-item__value">{{ $circular->classification?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('government_circulars.fields.issue_date') }}</span>
                    <span class="gc-detail-item__value">{{ optional($circular->issue_date)->format('Y-m-d H:i') ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('government_circulars.fields.received_date') }}</span>
                    <span class="gc-detail-item__value">{{ optional($circular->received_date)->format('Y-m-d H:i') ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('government_circulars.fields.receiving_mechanism') }}</span>
                    <span class="gc-detail-item__value">{{ $circular->receivingMechanism?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('government_circulars.fields.notification_type') }}</span>
                    <span class="gc-detail-item__value">{{ $circular->notificationType?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('government_circulars.fields.branch') }}</span>
                    <span class="gc-detail-item__value">{{ $circular->branch?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('government_circulars.fields.section') }}</span>
                    <span class="gc-detail-item__value">{{ $circular->section?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item" style="grid-column: 1 / -1;">
                    <span class="gc-detail-item__label">{{ __('government_circulars.fields.subject') }}</span>
                    <span class="gc-detail-item__value">{{ $circular->subject ?: '—' }}</span>
                </div>
                <div class="gc-detail-item" style="grid-column: 1 / -1;">
                    <span class="gc-detail-item__label">{{ __('government_circulars.fields.attachments') }}</span>
                    <span class="gc-detail-item__value">
                        @if ($attachmentUrl)
                            <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener">{{ __('government_circulars.open_attachment') }}</a>
                        @else
                            {{ __('government_circulars.no_attachment') }}
                        @endif
                    </span>
                </div>
            </div>
        </section>
    </div>

    {{-- Update status modal --}}
    <div
        class="modal fade gc-status-modal"
        id="gcStatusModal"
        tabindex="-1"
        aria-labelledby="gcStatusModalLabel"
        aria-hidden="true"
        data-gc-open-on-load="{{ $openStatusModal ? '1' : '0' }}"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5 mb-0" id="gcStatusModalLabel">{{ __('government_circulars.status_form.title') }}</h2>
                        <p class="text-muted small mb-0 mt-1">{{ __('government_circulars.status_form.subtitle') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('government_circulars.actions.cancel') }}"></button>
                </div>

                @if ($updatableStatuses->isEmpty())
                    <div class="modal-body">
                        <div class="gc-empty">{{ __('government_circulars.status_form.empty_statuses') }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('government_circulars.actions.cancel') }}</button>
                    </div>
                @else
                    <form method="POST" action="{{ route('modules.government-circulars.status', $circular->id) }}" enctype="multipart/form-data" novalidate>
                        @csrf
                        <div class="modal-body">
                            <div class="gc-form-grid">
                                <div class="gc-field">
                                    <label for="status_id">{{ __('government_circulars.status_form.status') }}</label>
                                    <select id="status_id" name="status_id" class="form-select @error('status_id') is-invalid @enderror" required>
                                        <option value="">—</option>
                                        @foreach ($updatableStatuses as $status)
                                            <option value="{{ $status->id }}" @selected((int) old('status_id') === (int) $status->id)>
                                                {{ $status->localizedName() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="gc-field gc-span-2">
                                    <label for="details">{{ __('government_circulars.status_form.details') }}</label>
                                    <textarea id="details" name="details" rows="3" class="form-control @error('details') is-invalid @enderror" maxlength="2000">{{ old('details') }}</textarea>
                                    @error('details') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="gc-field gc-span-2">
                                    <label for="attachment_file">{{ __('government_circulars.status_form.attachment') }}</label>
                                    <input id="attachment_file" type="file" name="attachment_file" class="form-control @error('attachment_file') is-invalid @enderror">
                                    <div class="form-text">{{ __('government_circulars.status_form.attachment_hint') }}</div>
                                    @error('attachment_file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('government_circulars.actions.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('government_circulars.status_form.submit') }}</button>
                        </div>
                    </form>
                @endif
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
                var menuWidth = menu.offsetWidth || 220;
                var top = rect.bottom + 6;
                var left = isRtl ? rect.right - menuWidth : rect.left;
                left = Math.max(8, Math.min(left, window.innerWidth - menuWidth - 8));
                menu.style.position = 'fixed';
                menu.style.top = top + 'px';
                menu.style.left = left + 'px';
                menu.style.zIndex = '1080';
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

                var modalTrigger = event.target.closest('[data-bs-toggle="modal"]');
                if (modalTrigger && modalTrigger.closest('.gc-actions__menu')) {
                    closeMenu();
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
                    return;
                }

                if (!openMenu) return;
                if (event.target.closest('.gc-actions__menu') || event.target.closest('.gc-actions__toggle')) {
                    return;
                }
                closeMenu();
            });

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

            window.addEventListener('resize', closeMenu);
            window.addEventListener('scroll', closeMenu, true);

            var modalEl = document.getElementById('gcStatusModal');
            if (modalEl) {
                // Escape layout transform/filter so fixed positioning is viewport-based.
                if (modalEl.parentElement !== document.body) {
                    document.body.appendChild(modalEl);
                }
                if (modalEl.getAttribute('data-gc-open-on-load') === '1' && window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            }

            var receiptModalEl = document.getElementById('gcReceiptModal');
            if (receiptModalEl && receiptModalEl.parentElement !== document.body) {
                document.body.appendChild(receiptModalEl);
            }
        })();
    </script>
@endpush
