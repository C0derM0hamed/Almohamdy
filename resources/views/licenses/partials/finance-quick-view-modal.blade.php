<div class="modal fade lic-operation-modal lic-quick-view-modal" id="licenseFinanceQuickViewModal" tabindex="-1" aria-labelledby="licenseFinanceQuickViewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-6" id="licenseFinanceQuickViewModalTitle"><i class="bi bi-wallet2" aria-hidden="true"></i>{{ __('licenses.payments.quick_view_title') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('licenses.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="lic-quick-view__hero">
                    <div class="lic-quick-view__hero-copy">
                        <span class="lic-quick-view__kicker">{{ __('licenses.payments.request_number') }}</span>
                        <span class="lic-quick-view__number lic-sensitive" data-license-preview-field="number"></span>
                        <h3 data-license-preview-field="title"></h3>
                    </div>
                    <span class="lic-status" data-license-preview-field="status"></span>
                </div>
                <div class="lic-quick-view__facts">
                    @foreach ([
                        ['license_number', 'bi-hash'],
                        ['amount', 'bi-cash-stack'],
                        ['invoice_number', 'bi-receipt'],
                        ['bank_name', 'bi-bank'],
                        ['requested_by', 'bi-person'],
                        ['requested_at', 'bi-calendar-event'],
                    ] as [$field, $icon])
                        <article class="lic-quick-view__fact">
                            <span class="lic-quick-view__fact-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                            <div>
                                <small>{{ $field === 'license_number' ? __('licenses.fields.license_number') : __('licenses.payments.'.$field) }}</small>
                                <strong class="{{ in_array($field, ['amount', 'invoice_number'], true) ? 'lic-sensitive' : '' }}" data-license-preview-field="{{ $field }}">—</strong>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="lic-btn" data-bs-dismiss="modal">{{ __('licenses.close') }}</button>
                <a class="lic-btn lic-btn--primary" href="#" data-license-preview-open>
                    <i class="bi bi-arrow-up-left" aria-hidden="true"></i>{{ __('licenses.quick_view.more_details') }}
                </a>
            </div>
        </div>
    </div>
</div>
