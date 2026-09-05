<div class="modal fade lic-operation-modal lic-quick-view-modal" id="govNoticeQuickViewModal" tabindex="-1" aria-labelledby="govNoticeQuickViewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-6" id="govNoticeQuickViewModalTitle"><i class="bi bi-calendar-event" aria-hidden="true"></i>{{ __('gov_accounts.notices.quick_view_title') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('gov_accounts.actions.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="lic-quick-view__hero">
                    <div class="lic-quick-view__hero-copy">
                        <span class="lic-quick-view__kicker">{{ __('gov_accounts.export.notice') }}</span>
                        <span class="lic-quick-view__number" data-license-preview-field="number"></span>
                        <h3 data-license-preview-field="title"></h3>
                    </div>
                    <span class="lic-status" data-license-preview-field="status"></span>
                </div>
                <div class="lic-quick-view__facts">
                    @foreach ([
                        ['authority', 'bi-bank'],
                        ['branch', 'bi-building'],
                        ['event_at', 'bi-calendar-event'],
                        ['attendance', 'bi-people'],
                        ['targeting', 'bi-funnel'],
                        ['service', 'bi-grid-1x2'],
                    ] as [$field, $icon])
                        <article class="lic-quick-view__fact">
                            <span class="lic-quick-view__fact-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                            <div>
                                <small>{{ $field === 'event_at' ? __('gov_accounts.fields.event_date') : ($field === 'attendance' ? __('gov_accounts.fields.attendance_method') : ($field === 'targeting' ? __('gov_accounts.fields.targeting') : __('gov_accounts.fields.'.$field))) }}</small>
                                <strong data-license-preview-field="{{ $field }}">—</strong>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="lic-btn" data-bs-dismiss="modal">{{ __('gov_accounts.actions.close') }}</button>
                <a class="lic-btn lic-btn--primary" href="#" data-license-preview-open>
                    <i class="bi bi-arrow-up-left" aria-hidden="true"></i>{{ __('gov_accounts.notices.more_details') }}
                </a>
            </div>
        </div>
    </div>
</div>
