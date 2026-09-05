<div class="modal fade lic-operation-modal lic-quick-view-modal" id="govHrLifecycleModal" tabindex="-1" aria-labelledby="govHrLifecycleModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-6" id="govHrLifecycleModalTitle"><i class="bi bi-slash-circle" aria-hidden="true"></i>{{ __('gov_accounts.hr.modal_title') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('gov_accounts.actions.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="lic-quick-view__hero">
                    <div class="lic-quick-view__hero-copy">
                        <span class="lic-quick-view__kicker">{{ __('gov_accounts.fields.username') }}</span>
                        <span class="lic-quick-view__number lic-sensitive" data-license-preview-field="username"></span>
                        <h3 data-license-preview-field="employee"></h3>
                    </div>
                    <span class="lic-status" data-license-preview-field="status"></span>
                </div>
                <div class="lic-quick-view__facts">
                    @foreach ([
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
                <form method="POST" class="mt-4" data-license-preview-form>
                    @csrf
                    <div class="lic-field mb-3">
                        <label for="gov_hr_type">{{ __('gov_accounts.export.type') }}</label>
                        <select id="gov_hr_type" name="type" class="form-select" required>
                            <option value="suspend">{{ __('gov_accounts.types.suspend') }}</option>
                            <option value="close">{{ __('gov_accounts.types.close') }}</option>
                        </select>
                    </div>
                    <div class="lic-field mb-3">
                        <label for="gov_hr_justification">{{ __('gov_accounts.fields.justification') }}</label>
                        <textarea id="gov_hr_justification" name="justification" class="form-control" required minlength="3" maxlength="5000"></textarea>
                    </div>
                    <button class="lic-btn lic-btn--primary" type="submit">{{ __('gov_accounts.actions.submit') }}</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="lic-btn" data-bs-dismiss="modal">{{ __('gov_accounts.actions.close') }}</button>
                <a class="lic-btn lic-btn--primary" href="#" data-license-preview-open>
                    <i class="bi bi-arrow-up-left" aria-hidden="true"></i>{{ __('gov_accounts.actions.view') }}
                </a>
            </div>
        </div>
    </div>
</div>
