<div class="modal fade lic-operation-modal lic-quick-view-modal" id="licenseQuickViewModal" tabindex="-1" aria-labelledby="licenseQuickViewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-6" id="licenseQuickViewModalTitle"><i class="bi bi-patch-check" aria-hidden="true"></i>{{ __('licenses.quick_view.title') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('licenses.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="lic-quick-view__hero">
                    <div class="lic-quick-view__hero-copy">
                        <span class="lic-quick-view__kicker">{{ __('licenses.fields.license_number') }}</span>
                        <span class="lic-quick-view__number lic-sensitive" data-license-preview-field="number"></span>
                        <h3 data-license-preview-field="title"></h3>
                    </div>
                    <span class="lic-status" data-license-preview-field="status"></span>
                </div>
                <div class="lic-quick-view__facts">
                    @foreach ([
                        ['type', 'bi-award'],
                        ['authority', 'bi-building'],
                        ['hospital_branch', 'bi-hospital'],
                        ['responsible', 'bi-person'],
                        ['expiry_date', 'bi-calendar-event'],
                        ['renewal_stage', 'bi-signpost-2'],
                    ] as [$field, $icon])
                        <article class="lic-quick-view__fact">
                            <span class="lic-quick-view__fact-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                            <div>
                                <small>{{ __('licenses.quick_view.'.$field) }}</small>
                                <strong data-license-preview-field="{{ $field }}">—</strong>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="lic-quick-view__departments">
                    <small>{{ __('licenses.fields.departments') }}</small>
                    <div class="lic-chip-list lic-chip-list--compact" data-license-preview-departments></div>
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
