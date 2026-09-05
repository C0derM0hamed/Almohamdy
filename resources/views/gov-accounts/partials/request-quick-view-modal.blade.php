<div class="modal fade lic-operation-modal lic-quick-view-modal" id="govRequestQuickViewModal" tabindex="-1" aria-labelledby="govRequestQuickViewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-6" id="govRequestQuickViewModalTitle"><i class="bi bi-file-earmark-person" aria-hidden="true"></i>{{ __('gov_accounts.quick_view.title') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('gov_accounts.actions.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="lic-quick-view__hero">
                    <div class="lic-quick-view__hero-copy">
                        <span class="lic-quick-view__kicker">{{ __('gov_accounts.quick_view.number') }}</span>
                        <span class="lic-quick-view__number" data-license-preview-field="number"></span>
                        <h3 data-license-preview-field="title"></h3>
                    </div>
                    <span class="lic-status" data-license-preview-field="status"></span>
                </div>
                <div class="lic-quick-view__facts">
                    @foreach ([
                        ['employee', 'bi-person'],
                        ['department', 'bi-diagram-3'],
                        ['authority', 'bi-bank'],
                        ['service', 'bi-grid-1x2'],
                        ['role', 'bi-person-badge'],
                        ['branch', 'bi-building'],
                    ] as [$field, $icon])
                        <article class="lic-quick-view__fact">
                            <span class="lic-quick-view__fact-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                            <div>
                                <small>{{ __('gov_accounts.fields.'.$field) }}</small>
                                <strong data-license-preview-field="{{ $field }}">—</strong>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="lic-btn" data-bs-dismiss="modal">{{ __('gov_accounts.actions.close') }}</button>
                <a class="lic-btn lic-btn--primary" href="#" data-license-preview-open>
                    <i class="bi bi-arrow-up-left" aria-hidden="true"></i>{{ __('gov_accounts.quick_view.more_details') }}
                </a>
            </div>
        </div>
    </div>
</div>
