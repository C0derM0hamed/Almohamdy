@extends('layouts.app')

@section('title', __('licenses.show'))
@section('sidebar_heading', __('licenses.title'))
@section('sidebar_subheading', __('licenses.subtitle'))

@push('styles')
<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $url = static fn ($name, $params = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : '#';
    $nameOf = static function ($item) {
        if (! $item) return '—';
        if (is_string($item)) return $item;
        if (method_exists($item, 'displayName')) return $item->displayName();
        if (method_exists($item, 'localizedName')) return $item->localizedName();
        $field = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
        return data_get($item, $field) ?: data_get($item, 'name') ?: data_get($item, 'hr_name') ?: data_get($item, 'full_name') ?: '—';
    };
    $dateOf = static function ($value, $withTime = false) {
        if (! $value) return '—';
        try { return ($value instanceof \DateTimeInterface ? $value : \Illuminate\Support\Carbon::parse($value))->format($withTime ? 'Y-m-d H:i' : 'Y-m-d'); } catch (\Throwable) { return (string) $value; }
    };
    $timelineNotice = static function ($notice) {
        if (! is_string($notice) || ! str_starts_with($notice, 'licenses.timeline.')) return $notice;
        $key = substr($notice, strlen('licenses.timeline.'));
        $eventKey = 'licenses.timeline.events.'.$key;
        $event = __($eventKey);
        if ($event !== $eventKey) return $event;
        $rootKey = 'licenses.timeline.'.$key;
        $root = __($rootKey);
        return $root !== $rootKey ? $root : $notice;
    };
    $recordId = $license->getRouteKey();
    $status = $license->statusRelation ?? $license->status ?? null;
    $statusKey = data_get($status, 'key') ?: data_get($status, 'code') ?: data_get($status, 'slug') ?: (is_string($status) ? $status : 'unknown');
    $alertWindow = app(\App\Support\Licenses\LicenseAlertWindow::class)->for($license->expiry_date);
    $alertStatusKey = match ($alertWindow) {
        \App\Support\Licenses\LicenseAlertWindow::YELLOW,
        \App\Support\Licenses\LicenseAlertWindow::SIXTY_DAYS => 'near_expiry',
        \App\Support\Licenses\LicenseAlertWindow::RED,
        \App\Support\Licenses\LicenseAlertWindow::EXPIRED => 'expired',
        default => 'active',
    };
    $responsible = $license->responsibleUser ?? $license->responsible ?? null;
    $currentUndertaking = $currentUndertaking ?? $license->currentUndertaking ?? ($license->undertakings?->sortByDesc('id')->first());
    $comments = $comments ?? $license->comments ?? collect();
    $attachments = $attachments ?? $license->attachments ?? collect();
    $payments = $paymentRequests ?? $license->paymentRequests ?? collect();
    $renewals = $renewalHistory ?? $license->renewals ?? collect();
    $timeline = $timelineEntries ?? $license->timelineEntries ?? $license->timeline ?? collect();
    $notifications = $notificationLog ?? $license->notifications ?? collect();
    $stagesList = $stageOptions ?? $renewalStages ?? $stages ?? collect();
    $responsiblesList = $responsibleOptions ?? $responsibleUsers ?? $users ?? collect();
    $paymentStatusesList = $paymentStatusOptions ?? collect();
    $canAdminUi = (bool) ($canAdmin ?? $permissions['admin'] ?? ((int) session('hr_user_level') === 3));
    $canProcessUi = (bool) ($canProcess ?? $permissions['process'] ?? $canAdminUi || (string) data_get($responsible, 'hr_id', data_get($responsible, 'id')) === (string) session('hr_user_id'));
    $canFinanceUi = (bool) ($canFinance ?? $permissions['finance'] ?? $canAdminUi);
    $title = $license->title ?: $nameOf($license->licenseType ?? $license->type ?? null);
    $openRenewal = $renewals->first(fn ($renewal) => ! $renewal->completed_at);
    $oldOperation = old('_operation');
@endphp

<div class="hm-licenses">
    @php
        $actions = '<a class="lic-btn" href="'.e($url('modules.licenses.index')).'"><i class="bi bi-arrow-left"></i>'.e(__('licenses.back')).'</a>';
        $actions .= '<a class="lic-btn" href="'.e($url('modules.licenses.pdf', $recordId)).'" target="_blank"><i class="bi bi-printer"></i>'.e(__('licenses.print')).'</a>';
        if ($canAdminUi) $actions .= '<a class="lic-btn lic-btn--primary" href="'.e($url('modules.licenses.edit', $recordId)).'"><i class="bi bi-pencil"></i>'.e(__('licenses.edit')).'</a>';
    @endphp
    @include('licenses.partials.page-header', ['title' => $title ?: __('licenses.show'), 'subtitle' => $license->license_number ?: '#'.$license->id, 'icon' => 'bi-patch-check', 'actions' => new \Illuminate\Support\HtmlString($actions)])
    @include('licenses.partials.feedback')

    <section id="summary" class="lic-panel lic-anchor" aria-labelledby="summaryTitle">
        <div class="lic-panel__head">
            <h2 id="summaryTitle" class="lic-panel__title"><i class="bi bi-card-list"></i>{{ __('licenses.sections.summary') }}</h2>
            <span class="lic-status lic-status--{{ $alertStatusKey }}" title="{{ $nameOf($status) }}">{{ $nameOf($status) }}</span>
        </div>
        <div class="lic-summary-grid">
            @foreach ([
                __('licenses.fields.license_number') => $license->license_number ?: '#'.$license->id,
                __('licenses.fields.type') => $nameOf($license->licenseType ?? $license->type ?? null),
                __('licenses.fields.authority') => $nameOf($license->authority ?? null),
                __('licenses.fields.hospital_branch') => $nameOf($license->hospitalBranch ?? null),
                __('licenses.fields.responsible') => $nameOf($responsible),
                __('licenses.fields.issue_date') => $dateOf($license->issue_date),
                __('licenses.fields.expiry_date') => $dateOf($license->expiry_date),
                __('licenses.fields.renewal_stage') => $nameOf($license->renewalStage ?? $license->stage ?? null),
                __('licenses.fields.updated_at') => $dateOf($license->updated_at, true),
            ] as $label => $value)
                <div class="lic-summary-item"><span class="lic-summary-item__label">{{ $label }}</span><span class="lic-summary-item__value">{{ $value }}</span></div>
            @endforeach
        </div>
        <div class="mt-3">
            <span class="lic-label">{{ __('licenses.fields.departments') }}</span>
            @include('licenses.partials.department-chips', ['departments' => $license->departments ?? $license->branches ?? collect()])
        </div>
        @if ($license->notes)<div class="mt-3"><span class="lic-label">{{ __('licenses.fields.notes') }}</span><p class="mb-0 text-break" style="white-space:pre-line">{{ $license->notes }}</p></div>@endif
    </section>

    @if($canProcessUi || $canAdminUi)
        <section class="lic-panel lic-action-hub lic-no-print" aria-labelledby="licenseActionsTitle">
            <div class="lic-panel__head"><h2 id="licenseActionsTitle" class="lic-panel__title"><i class="bi bi-sliders"></i>{{ __('licenses.sections.actions') }}</h2></div>
            <div class="lic-action-grid">
                @if($canProcessUi)
                    <button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#licenseOperationStage"><i class="bi bi-signpost"></i>{{ __('licenses.renewal.update_stage') }}</button>
                    @if($openRenewal)
                        <button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#licenseOperationRenewalComplete"><i class="bi bi-check2-circle"></i>{{ __('licenses.renewal.complete') }}</button>
                    @else
                        <button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#licenseOperationRenewalStart"><i class="bi bi-play-circle"></i>{{ __('licenses.renewal.start') }}</button>
                    @endif
                    <button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#licenseOperationPayment"><i class="bi bi-cash-stack"></i>{{ __('licenses.payments.create') }}</button>
                    <button class="lic-btn lic-action-button" type="button" data-license-open-panel="attachments"><i class="bi bi-paperclip"></i>{{ __('licenses.attachments.upload') }}</button>
                    <button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#licenseOperationComment"><i class="bi bi-chat-left-text"></i>{{ __('licenses.comments.add') }}</button>
                    <button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#licenseOperationExternal"><i class="bi bi-envelope-paper"></i>{{ __('licenses.external.add') }}</button>
                @endif
                @if($canAdminUi)<button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#licenseOperationAssignment"><i class="bi bi-person-gear"></i>{{ __('licenses.assignment.reassign') }}</button>@endif
            </div>
        </section>
    @endif

    <nav class="lic-tabs lic-no-print" role="tablist" aria-label="{{ __('licenses.show') }}">
        @foreach (['responsibility','comments','attachments','payments','history','timeline','notifications'] as $anchor)
            <a class="lic-tab" data-license-tab role="tab" aria-controls="{{ $anchor }}" href="#{{ $anchor }}">{{ __('licenses.sections.'.$anchor) }}</a>
        @endforeach
    </nav>

    @if($oldOperation === 'attachment')
        <span hidden data-license-initial-panel="attachments"></span>
    @elseif($oldOperation)
        <span hidden data-license-open-operation="licenseOperation{{ Illuminate\Support\Str::studly($oldOperation) }}"></span>
    @endif

    <div class="lic-two-column lic-read-workspace">
        <div class="lic-stack">
            <section id="responsibility" data-license-panel role="tabpanel" class="lic-panel lic-anchor" aria-labelledby="undertakingTitle">
                <div class="lic-panel__head"><h2 id="undertakingTitle" class="lic-panel__title"><i class="bi bi-shield-check"></i>{{ __('licenses.sections.responsibility') }}</h2>
                    @php $undertakingStatus = data_get($currentUndertaking, 'status', 'pending'); @endphp
                    <span class="lic-status {{ $undertakingStatus === 'accepted' ? 'lic-status--active' : ($undertakingStatus === 'escalated' ? 'lic-status--expired' : 'lic-status--near_expiry') }}">{{ __('licenses.undertaking.'.$undertakingStatus) }}</span>
                </div>
                @if ($currentUndertaking)
                    <div class="lic-summary-grid">
                        <div class="lic-summary-item"><span class="lic-summary-item__label">{{ __('licenses.undertaking.requested_at') }}</span><span class="lic-summary-item__value">{{ $dateOf($currentUndertaking->requested_at, true) }}</span></div>
                        <div class="lic-summary-item"><span class="lic-summary-item__label">{{ __('licenses.undertaking.accepted_at') }}</span><span class="lic-summary-item__value">{{ $dateOf($currentUndertaking->accepted_at, true) }}</span></div>
                        <div class="lic-summary-item"><span class="lic-summary-item__label">{{ __('licenses.undertaking.accepted_by') }}</span><span class="lic-summary-item__value">{{ $currentUndertaking->accepted_at ? $nameOf($currentUndertaking->user) : '—' }}</span></div>
                        <div class="lic-summary-item"><span class="lic-summary-item__label">{{ __('licenses.fields.status') }}</span><span class="lic-summary-item__value">{{ __('licenses.undertaking.'.$undertakingStatus) }}</span></div>
                    </div>
                    @if ($currentUndertaking->undertaking_text)<details class="mt-3"><summary class="lic-label">{{ __('licenses.undertaking.snapshot') }}</summary><p class="lic-gate__text">{{ $currentUndertaking->undertaking_text }}</p></details>@endif
                @else
                    <div class="lic-empty">{{ __('licenses.not_available') }}</div>
                @endif
            </section>

            <section id="renewal" class="lic-operation-sources lic-no-print" aria-labelledby="renewalTitle">
                <h2 id="renewalTitle" class="lic-panel__title"><i class="bi bi-arrow-repeat"></i>{{ __('licenses.sections.renewal') }}</h2>
                @if ($canProcessUi)
                    <div class="lic-form-grid">
                        <form method="POST" action="{{ $url('modules.licenses.stage', $recordId) }}" class="lic-field lic-field--span-2" data-license-operation-form="licenseOperationStage">
                            @csrf
                            <input type="hidden" name="_operation" value="stage">
                            <label for="renewal_stage_id">{{ __('licenses.renewal.update_stage') }}</label>
                            <div class="d-flex flex-wrap gap-2">
                                <select id="renewal_stage_id" name="renewal_stage_id" required class="form-select flex-grow-1 @error('renewal_stage_id') is-invalid @enderror">
                                    <option value="">—</option>@foreach ($stagesList as $stage)<option value="{{ $stage->id }}" @selected((string) old('renewal_stage_id', $license->renewal_stage_id) === (string) $stage->id)>{{ $nameOf($stage) }}</option>@endforeach
                                </select>
                                <button class="lic-btn lic-btn--primary" type="submit">{{ __('licenses.renewal.update_stage') }}</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ $url('modules.licenses.renewal.start', $recordId) }}" class="lic-field" data-license-operation-form="licenseOperationRenewalStart">
                            @csrf
                            <input type="hidden" name="_operation" value="renewal_start">
                            <label for="renewal_notes">{{ __('licenses.renewal.start') }}</label>
                            <textarea id="renewal_notes" name="notes" class="form-control" placeholder="{{ __('licenses.fields.notes') }}">{{ old('notes') }}</textarea>
                            <button class="lic-btn mt-2" type="submit"><i class="bi bi-play-circle"></i>{{ __('licenses.renewal.start') }}</button>
                        </form>
                        <form method="POST" action="{{ $url('modules.licenses.renewal.complete', $recordId) }}" enctype="multipart/form-data" class="lic-field" data-license-operation-form="licenseOperationRenewalComplete">
                            @csrf
                            <input type="hidden" name="_operation" value="renewal_complete">
                            <input type="hidden" name="old_expiry_date" value="{{ $dateOf($license->expiry_date) }}">
                            <label for="new_expiry_date">{{ __('licenses.renewal.new_expiry_date') }} <span class="lic-required">*</span></label>
                            <input id="new_expiry_date" type="date" name="new_expiry_date" min="{{ $dateOf($license->expiry_date) }}" required class="form-control @error('new_expiry_date') is-invalid @enderror">
                            <label for="new_license_copy" class="mt-2">{{ __('licenses.renewal.new_copy') }}</label>
                            <input id="new_license_copy" type="file" name="license_copy" accept=".pdf,.png,.jpg,.jpeg" class="form-control">
                            <p class="lic-help">{{ __('licenses.renewal.complete_help') }}</p>
                            <button class="lic-btn lic-btn--success mt-2" type="submit"><i class="bi bi-check2-circle"></i>{{ __('licenses.renewal.complete') }}</button>
                        </form>
                    </div>
                @else
                    <div class="lic-empty">{{ __('licenses.not_available') }}</div>
                @endif
            </section>

            <section id="comments" data-license-panel role="tabpanel" class="lic-panel lic-anchor" aria-labelledby="commentsTitle">
                <h2 id="commentsTitle" class="lic-panel__title"><i class="bi bi-chat-left-text"></i>{{ __('licenses.sections.comments') }}</h2>
                @forelse ($comments->sortByDesc('id') as $comment)
                    <article class="lic-comment"><div class="lic-comment__meta"><span class="lic-comment__author">{{ $nameOf($comment->user ?? $comment->author ?? null) }}</span><time>{{ $dateOf($comment->created_at, true) }}</time></div><p class="lic-comment__body">{{ $comment->body }}</p></article>
                @empty <div class="lic-empty">{{ __('licenses.comments.empty') }}</div> @endforelse
                @if ($canProcessUi)
                    <form method="POST" action="{{ $url('modules.licenses.comments.store', $recordId) }}" class="mt-3" data-license-operation-form="licenseOperationComment">
                        @csrf
                        <input type="hidden" name="_operation" value="comment">
                        <div class="lic-field"><label for="comment_body">{{ __('licenses.comments.add') }}</label><textarea id="comment_body" name="body" required maxlength="5000" placeholder="{{ __('licenses.comments.placeholder') }}" class="form-control @error('body') is-invalid @enderror">{{ old('body') }}</textarea>@error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <button class="lic-btn lic-btn--primary mt-2" type="submit"><i class="bi bi-send"></i>{{ __('licenses.comments.add') }}</button>
                    </form>
                @endif
            </section>

            <section id="attachments" data-license-panel role="tabpanel" class="lic-panel lic-anchor" aria-labelledby="attachmentsTitle">
                <div class="lic-panel__head">
                    <h2 id="attachmentsTitle" class="lic-panel__title"><i class="bi bi-paperclip"></i>{{ __('licenses.sections.attachments') }}</h2>
                    <span class="lic-file-count">{{ $attachments->count() }}</span>
                </div>
                @if ($canProcessUi)
                    <form method="POST" action="{{ $url('modules.licenses.attachments.store', $recordId) }}" enctype="multipart/form-data" class="lic-file-upload lic-file-upload--stack">
                        @csrf
                        <input type="hidden" name="_operation" value="attachment">
                        <div class="lic-form-grid">
                            <div class="lic-field"><label for="attachment_file">{{ __('licenses.attachments.file') }} <span class="lic-required">*</span></label><input id="attachment_file" type="file" name="file" accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx" required class="form-control"></div>
                            <div class="lic-field"><label for="attachment_context">{{ __('licenses.attachments.context') }}</label><select id="attachment_context" name="context" class="form-select">@foreach(['license','renewal'] as $context)<option value="{{ $context }}">{{ __('licenses.attachments.contexts.'.$context) }}</option>@endforeach</select></div>
                            <div class="lic-field lic-field--span-2"><label for="attachment_description">{{ __('licenses.fields.description') }}</label><input id="attachment_description" type="text" name="description" maxlength="500" class="form-control"></div>
                            <div class="lic-field lic-field--span-2"><p class="lic-help mb-2">{{ __('licenses.attachments.allowed') }}</p><button class="lic-btn lic-btn--primary" type="submit"><i class="bi bi-upload"></i>{{ __('licenses.attachments.upload') }}</button></div>
                        </div>
                    </form>
                @endif
                @include('licenses.partials.file-cards', [
                    'files' => $attachments->sortByDesc('id'),
                    'downloadUrl' => fn ($file) => $url('modules.licenses.attachments.download', [$recordId, $file->getRouteKey()]),
                    'subtitle' => fn ($file) => trim(implode(' · ', array_filter([
                        __('licenses.attachments.contexts.'.($file->context ?: 'license')),
                        $file->description,
                        $nameOf($file->uploader ?? $file->user ?? null),
                    ]))),
                ])
            </section>

            <section id="payments" data-license-panel role="tabpanel" class="lic-panel lic-anchor" aria-labelledby="paymentsTitle">
                <h2 id="paymentsTitle" class="lic-panel__title"><i class="bi bi-cash-stack"></i>{{ __('licenses.sections.payments') }}</h2>
                <div class="lic-table-wrap"><table class="lic-table lic-table--stack"><thead><tr><th>{{ __('licenses.payments.request_number') }}</th><th>{{ __('licenses.payments.amount') }}</th><th>{{ __('licenses.payments.status') }}</th><th>{{ __('licenses.payments.requested_at') }}</th><th>{{ __('licenses.actions') }}</th></tr></thead><tbody>
                @forelse ($payments->sortByDesc('id') as $payment)
                    @php $paymentStatus = $payment->statusRelation ?? $payment->status ?? null; @endphp
                    <tr><td>#{{ $payment->id }}</td><td class="lic-sensitive">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency ?: 'SAR' }}</td><td><span class="lic-status">{{ $nameOf($paymentStatus) }}</span></td><td>{{ $dateOf($payment->created_at, true) }}</td><td>@if($canFinanceUi)<a class="lic-btn lic-btn--sm" href="{{ $url('modules.licenses.finance.show', $payment->getRouteKey()) }}">{{ __('licenses.view') }}</a>@else — @endif</td></tr>
                @empty <tr><td colspan="5" class="lic-empty">{{ __('licenses.payments.empty') }}</td></tr> @endforelse
                </tbody></table></div>
                @if ($canProcessUi)
                    <form method="POST" action="{{ $url('modules.licenses.payment-requests.store', $recordId) }}" enctype="multipart/form-data" class="lic-form-grid mt-3" data-license-operation-form="licenseOperationPayment">
                        @csrf
                        <input type="hidden" name="_operation" value="payment">
                        <div class="lic-field"><label for="payment_amount">{{ __('licenses.payments.amount') }} <span class="lic-required">*</span></label><input id="payment_amount" type="number" name="amount" min="0.01" step="0.01" required value="{{ old('amount') }}" class="form-control"></div>
                        <div class="lic-field"><label for="payment_currency">{{ __('licenses.payments.currency') }}</label><input id="payment_currency" type="text" name="currency" value="{{ old('currency', 'SAR') }}" maxlength="3" class="form-control lic-sensitive"></div>
                        <div class="lic-field"><label for="bank_name">{{ __('licenses.payments.bank_name') }}</label><input id="bank_name" type="text" name="bank_name" value="{{ old('bank_name') }}" maxlength="255" class="form-control"></div>
                        <div class="lic-field"><label for="account_iban">{{ __('licenses.payments.account_iban') }}</label><input id="account_iban" type="text" name="account_iban" value="{{ old('account_iban') }}" maxlength="100" class="form-control lic-sensitive"></div>
                        <div class="lic-field"><label for="invoice_number">{{ __('licenses.payments.invoice_number') }}</label><input id="invoice_number" type="text" name="invoice_number" value="{{ old('invoice_number') }}" maxlength="100" class="form-control lic-sensitive"></div>
                        <div class="lic-field"><label for="payment_attachment">{{ __('licenses.fields.attachment') }}</label><input id="payment_attachment" type="file" name="attachments[]" multiple accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx" class="form-control"></div>
                        <div class="lic-field lic-field--span-2"><label for="transfer_details">{{ __('licenses.payments.transfer_details') }}</label><textarea id="transfer_details" name="transfer_details" class="form-control">{{ old('transfer_details') }}</textarea></div>
                        <div class="lic-field lic-field--span-2"><label for="payment_notes">{{ __('licenses.fields.notes') }}</label><textarea id="payment_notes" name="notes" class="form-control">{{ old('notes') }}</textarea><button class="lic-btn lic-btn--primary mt-2" type="submit"><i class="bi bi-send"></i>{{ __('licenses.payments.create') }}</button></div>
                    </form>
                @endif
            </section>

            <section id="history" data-license-panel role="tabpanel" class="lic-panel lic-anchor" aria-labelledby="historyTitle">
                <h2 id="historyTitle" class="lic-panel__title"><i class="bi bi-clock-history"></i>{{ __('licenses.sections.history') }}</h2>
                <div class="lic-table-wrap"><table class="lic-table lic-table--stack"><thead><tr><th>{{ __('licenses.renewal.previous_expiry') }}</th><th>{{ __('licenses.renewal.new_expiry') }}</th><th>{{ __('licenses.renewal.started_at') }}</th><th>{{ __('licenses.renewal.completed_at') }}</th><th>{{ __('licenses.fields.notes') }}</th></tr></thead><tbody>
                @forelse ($renewals->sortByDesc('id') as $renewal)<tr><td>{{ $dateOf($renewal->previous_expiry_date) }}</td><td>{{ $dateOf($renewal->new_expiry_date) }}</td><td>{{ $dateOf($renewal->started_at, true) }}</td><td>{{ $dateOf($renewal->completed_at, true) }}</td><td>{{ $renewal->notes ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="lic-empty">{{ __('licenses.renewal.empty') }}</td></tr>@endforelse
                </tbody></table></div>
            </section>

            <section id="timeline" data-license-panel role="tabpanel" class="lic-panel lic-anchor" aria-labelledby="timelineTitle">
                <h2 id="timelineTitle" class="lic-panel__title"><i class="bi bi-signpost-split"></i>{{ __('licenses.sections.timeline') }}</h2>
                <ol class="lic-timeline">
                    @forelse ($timeline->sortByDesc('id') as $entry)
                        @php $eventKey = $entry->event_type ?: 'settings_changed'; @endphp
                        <li class="lic-timeline__item"><span class="lic-timeline__dot"></span><div class="lic-timeline__head"><span class="lic-timeline__title">{{ __('licenses.timeline.events.'.$eventKey) }}</span><time class="lic-timeline__date">{{ $dateOf($entry->date ?? $entry->created_at, true) }}</time></div><div class="lic-timeline__body">{{ $timelineNotice($entry->notice) ?: __('licenses.timeline.actor', ['name' => $nameOf($entry->creator ?? $entry->user ?? null)]) }}</div></li>
                    @empty <li class="lic-empty">{{ __('licenses.timeline.empty') }}</li> @endforelse
                </ol>
            </section>

            <section id="notifications" data-license-panel role="tabpanel" class="lic-panel lic-anchor" aria-labelledby="notificationsTitle">
                <h2 id="notificationsTitle" class="lic-panel__title"><i class="bi bi-bell"></i>{{ __('licenses.sections.notifications') }}</h2>
                <div class="lic-table-wrap"><table class="lic-table lic-table--stack"><thead><tr><th>{{ __('licenses.notifications.event') }}</th><th>{{ __('licenses.notifications.recipient') }}</th><th>{{ __('licenses.notifications.channel') }}</th><th>{{ __('licenses.notifications.delivery') }}</th><th>{{ __('licenses.notifications.sent_at') }}</th></tr></thead><tbody>
                @forelse ($notifications->sortByDesc('id') as $notification)
                    <tr><td>{{ __('licenses.timeline.events.'.($notification->event_type ?: 'reminder_sent')) }}</td><td>{{ $nameOf($notification->recipientUser ?? $notification->recipient ?? null) }}</td><td>{{ strtoupper($notification->channel ?: 'in-app') }}</td><td>{{ __('licenses.notifications.'.($notification->delivery_status ?: $notification->status ?: 'delivered')) }}</td><td>{{ $dateOf($notification->sent_at ?? $notification->created_at, true) }}</td></tr>
                @empty <tr><td colspan="5" class="lic-empty">{{ __('licenses.notifications.empty') }}</td></tr> @endforelse
                </tbody></table></div>
            </section>
        </div>

        <aside class="lic-stack lic-operation-sources lic-no-print">
            @if ($canAdminUi)
                <section class="lic-panel"><h2 class="lic-panel__title"><i class="bi bi-person-gear"></i>{{ __('licenses.sections.assignment') }}</h2><p class="lic-help">{{ __('licenses.assignment.warning') }}</p>
                    <form method="POST" action="{{ $url('modules.licenses.assign', $recordId) }}" data-license-operation-form="licenseOperationAssignment">@csrf<input type="hidden" name="_operation" value="assignment"><div class="lic-field"><label for="new_responsible_user_id">{{ __('licenses.fields.new_responsible') }}</label><select id="new_responsible_user_id" name="responsible_user_id" required class="form-select"><option value="">—</option>@foreach($responsiblesList as $user)<option value="{{ $user->hr_id ?? $user->id }}">{{ $nameOf($user) }}</option>@endforeach</select></div><button class="lic-btn mt-2" type="submit"><i class="bi bi-person-check"></i>{{ __('licenses.assignment.reassign') }}</button></form>
                </section>
            @endif
            @if ($canProcessUi)
                <section class="lic-panel"><h2 class="lic-panel__title"><i class="bi bi-envelope-paper"></i>{{ __('licenses.sections.external') }}</h2>
                    <form method="POST" action="{{ $url('modules.licenses.external-communications.store', $recordId) }}" enctype="multipart/form-data" data-license-operation-form="licenseOperationExternal">@csrf<input type="hidden" name="_operation" value="external">
                        <div class="lic-field mb-2"><label for="external_reference">{{ __('licenses.external.reference_no') }}</label><input id="external_reference" name="reference_no" maxlength="100" class="form-control lic-sensitive"></div>
                        <div class="lic-field mb-2"><label for="external_date">{{ __('licenses.external.letter_date') }}</label><input id="external_date" type="date" name="letter_date" class="form-control"></div>
                        <div class="lic-field mb-2"><label for="external_authority">{{ __('licenses.external.authority') }}</label><input id="external_authority" name="authority" maxlength="255" class="form-control"></div>
                        <div class="lic-field mb-2"><label for="external_description">{{ __('licenses.external.description') }} <span class="lic-required">*</span></label><textarea id="external_description" name="description" required class="form-control"></textarea></div>
                        <div class="lic-field mb-2"><label for="external_attachment">{{ __('licenses.fields.attachment') }}</label><input id="external_attachment" type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx" class="form-control"></div>
                        <button class="lic-btn lic-btn--primary" type="submit"><i class="bi bi-plus-circle"></i>{{ __('licenses.external.add') }}</button>
                    </form>
                </section>
            @endif
        </aside>
    </div>

    @include('licenses.partials.departments-modal')
    @if($canProcessUi)
        @include('licenses.partials.operation-modal', ['id'=>'licenseOperationStage','title'=>__('licenses.renewal.update_stage'),'icon'=>'bi-signpost','size'=>'modal-sm'])
        @include('licenses.partials.operation-modal', ['id'=>'licenseOperationRenewalStart','title'=>__('licenses.renewal.start'),'icon'=>'bi-play-circle'])
        @include('licenses.partials.operation-modal', ['id'=>'licenseOperationRenewalComplete','title'=>__('licenses.renewal.complete'),'icon'=>'bi-check2-circle','size'=>'modal-lg'])
        @include('licenses.partials.operation-modal', ['id'=>'licenseOperationPayment','title'=>__('licenses.payments.create'),'icon'=>'bi-cash-stack','size'=>'modal-lg'])
        @include('licenses.partials.operation-modal', ['id'=>'licenseOperationComment','title'=>__('licenses.comments.add'),'icon'=>'bi-chat-left-text'])
        @include('licenses.partials.operation-modal', ['id'=>'licenseOperationExternal','title'=>__('licenses.external.add'),'icon'=>'bi-envelope-paper'])
    @endif
    @if($canAdminUi)@include('licenses.partials.operation-modal', ['id'=>'licenseOperationAssignment','title'=>__('licenses.assignment.reassign'),'icon'=>'bi-person-gear','size'=>'modal-sm'])@endif
</div>
@endsection

@push('scripts')<script src="{{ asset('js/hm-licenses.js') }}?v={{ filemtime(public_path('js/hm-licenses.js')) }}"></script>@endpush
