@extends('layouts.app')
@section('title', __('gov_accounts.requests.number', ['id' => $accountRequest->id]))
@section('sidebar_heading', __('gov_accounts.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@php
    $nameOf = static function ($item) {
        if (! $item) return '—';
        if (method_exists($item, 'displayName')) return $item->displayName();
        if (method_exists($item, 'localizedName')) return $item->localizedName();
        $field = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        return data_get($item, $field) ?: data_get($item, 'name') ?: '—';
    };
    $dateOf = static fn ($value) => $value ? ($value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i') : substr((string) $value, 0, 16)) : '—';
    $dash = static fn ($value) => filled($value) ? $value : '—';
    $status = $accountRequest->status;
    $canHead = (bool) ($abilities['head'] ?? false);
    $canProcessor = (bool) ($abilities['processor'] ?? false);
    $canAttach = (bool) ($abilities['attach'] ?? false);
    $canEdit = $canHead && in_array($status, ['draft', 'rejected'], true);
    $canSubmit = $canHead && $status === 'draft';
    $canResubmit = $canHead && $status === 'rejected';
    $canReview = $canProcessor && $status === 'under_review';
    $canAuthority = $canProcessor && $status === 'approved';
    $canComplete = $canProcessor && $status === 'submitted_to_authority';
    $canCancel = ($canProcessor && $status === 'under_review')
        || ($canHead && in_array($status, ['draft', 'rejected', 'awaiting_employee'], true));
    $hasActions = $canEdit || $canSubmit || $canResubmit || $canReview || $canAuthority || $canComplete || $canCancel;
    $hasOperations = $canSubmit || $canResubmit || $canReview || $canAuthority || $canComplete;
    $fileIcon = static function (?string $name): string {
        $ext = strtolower((string) pathinfo((string) $name, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'bi-file-earmark-pdf',
            'xls', 'xlsx' => 'bi-file-earmark-spreadsheet',
            'jpg', 'jpeg', 'png' => 'bi-file-earmark-image',
            default => 'bi-file-earmark',
        };
    };
    $fileSize = static function ($bytes): string {
        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return '—';
        }
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / 1048576, 1).' MB';
    };
    $summary = [
        __('gov_accounts.fields.employee') => $nameOf($accountRequest->employee),
        __('gov_accounts.fields.branch') => $nameOf($accountRequest->hospitalBranch),
        __('gov_accounts.fields.department_unit') => $accountRequest->department?->hierarchyLabel() ?: '—',
        __('gov_accounts.fields.authority') => $nameOf($accountRequest->authority),
        __('gov_accounts.fields.service') => $nameOf($accountRequest->service),
        __('gov_accounts.fields.role') => $nameOf($accountRequest->role),
        __('gov_accounts.export.type') => __('gov_accounts.types.'.$accountRequest->type),
        __('gov_accounts.fields.round') => $accountRequest->round,
        __('gov_accounts.export.created_at') => $dateOf($accountRequest->created_at),
        __('gov_accounts.fields.reference_no') => $dash($accountRequest->authority_reference),
    ];
@endphp
@section('content')
<div class="hm-licenses">
    @include('licenses.partials.page-header', [
        'title' => __('gov_accounts.requests.number', ['id' => $accountRequest->id]),
        'subtitle' => __('gov_accounts.types.'.$accountRequest->type),
        'icon' => 'bi-file-earmark-person',
        'actions' => new \Illuminate\Support\HtmlString('<a class="lic-btn" href="'.e(route('modules.gov-accounts.requests.index')).'"><i class="bi bi-arrow-left"></i>'.e(__('gov_accounts.actions.back')).'</a>'),
    ])
    @include('licenses.partials.feedback')

    <section class="lic-panel">
        <div class="lic-panel__head">
            <h2 class="lic-panel__title"><i class="bi bi-info-circle"></i>{{ __('gov_accounts.requests.number', ['id' => $accountRequest->id]) }}</h2>
            <span class="lic-status lic-status--{{ $status }}">{{ __('gov_accounts.statuses.'.$status) }}</span>
        </div>
        <div class="lic-summary-grid">
            @foreach ($summary as $label => $value)
                <div class="lic-summary-item"><span class="lic-summary-item__label">{{ $label }}</span><span class="lic-summary-item__value">{{ $value }}</span></div>
            @endforeach
        </div>
        @if($accountRequest->justification)
            <div class="mt-3"><span class="lic-label">{{ __('gov_accounts.fields.justification') }}</span><p class="mb-0">{{ $accountRequest->justification }}</p></div>
        @endif
        @if($accountRequest->notes)
            <div class="mt-3"><span class="lic-label">{{ __('gov_accounts.fields.notes') }}</span><p class="mb-0">{{ $accountRequest->notes }}</p></div>
        @endif
        @if($accountRequest->rejection_reason)
            <div class="lic-alert lic-alert--danger mt-3">{{ $accountRequest->rejection_reason }}</div>
        @endif
    </section>

    @if($hasActions)
        <section class="lic-panel lic-action-hub lic-no-print" aria-labelledby="govRequestActionsTitle">
            <div class="lic-panel__head">
                <div>
                    <h2 id="govRequestActionsTitle" class="lic-panel__title"><i class="bi bi-sliders"></i>{{ __('gov_accounts.requests.actions') }}</h2>
                    <p class="lic-help mb-0">{{ __('gov_accounts.requests.actions_hint') }}</p>
                </div>
            </div>
            <div class="lic-action-grid">
                @if($canEdit)<a class="lic-btn lic-action-button" href="{{ route('modules.gov-accounts.requests.edit', $accountRequest) }}"><i class="bi bi-pencil"></i>{{ __('gov_accounts.actions.edit') }}</a>@endif
                @if($canSubmit)<button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#govRequestOperationSubmit"><i class="bi bi-send"></i>{{ __('gov_accounts.actions.submit') }}</button>@endif
                @if($canResubmit)<button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#govRequestOperationResubmit"><i class="bi bi-arrow-repeat"></i>{{ __('gov_accounts.actions.resubmit') }}</button>@endif
                @if($canReview)
                    <button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#govRequestOperationApprove"><i class="bi bi-check2-circle"></i>{{ __('gov_accounts.actions.approve') }}</button>
                    <button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#govRequestOperationReject"><i class="bi bi-x-circle"></i>{{ __('gov_accounts.actions.reject') }}</button>
                @endif
                @if($canAuthority)<button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#govRequestOperationAuthority"><i class="bi bi-box-arrow-up-right"></i>{{ __('gov_accounts.actions.mark_authority') }}</button>@endif
                @if($canComplete)<button class="lic-btn lic-action-button" type="button" data-bs-toggle="modal" data-bs-target="#govRequestOperationComplete"><i class="bi bi-patch-check"></i>{{ __('gov_accounts.actions.complete') }}</button>@endif
                @if($canCancel)
                    <form method="POST" action="{{ route('modules.gov-accounts.requests.cancel', $accountRequest) }}" onsubmit="return confirm(@js(__('gov_accounts.requests.cancel_confirm')));">
                        @csrf
                        <button class="lic-btn lic-action-button lic-btn--danger" type="submit"><i class="bi bi-slash-circle"></i>{{ __('gov_accounts.actions.cancel_request') }}</button>
                    </form>
                @endif
            </div>
        </section>
    @endif

    <section class="lic-panel">
        <div class="lic-panel__head">
            <h2 class="lic-panel__title"><i class="bi bi-paperclip"></i>{{ __('gov_accounts.attachments') }}</h2>
            <span class="gov-file-count">{{ $accountRequest->attachments->count() }}</span>
        </div>
        @if($canAttach)
            <form class="gov-file-upload" method="POST" enctype="multipart/form-data" action="{{ route('modules.gov-accounts.requests.attachments.store', $accountRequest) }}">
                @csrf
                <input type="hidden" name="context" value="request">
                <span class="gov-file-upload__icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></span>
                <div class="gov-file-upload__copy">
                    <strong>{{ __('gov_accounts.actions.upload') }}</strong>
                    <small>{{ __('gov_accounts.requests.upload_hint') }}</small>
                </div>
                <input id="gov_request_attachment" type="file" name="attachment" required accept=".pdf,.jpg,.jpeg,.png,.xls,.xlsx" aria-label="{{ __('gov_accounts.fields.file') }}">
                <button class="lic-btn lic-btn--primary" type="submit">{{ __('gov_accounts.actions.upload') }}</button>
            </form>
        @endif
        <div class="gov-file-list">
            @forelse($accountRequest->attachments->sortByDesc('id') as $attachment)
                <article class="gov-file-card">
                    <span class="gov-file-card__icon" aria-hidden="true"><i class="bi {{ $fileIcon($attachment->original_name) }}"></i></span>
                    <div class="gov-file-card__copy">
                        <strong>{{ $attachment->original_name }}</strong>
                        <small>{{ $fileSize($attachment->size) }} · {{ $dateOf($attachment->uploaded_at) }}</small>
                    </div>
                    <a class="lic-btn lic-btn--sm" href="{{ route('modules.gov-accounts.requests.attachments.download', [$accountRequest, $attachment]) }}" download="{{ $attachment->original_name }}">
                        <i class="bi bi-download" aria-hidden="true"></i>{{ __('gov_accounts.actions.download') }}
                    </a>
                </article>
            @empty
                <p class="gov-file-empty">{{ __('gov_accounts.requests.empty_attachments') }}</p>
            @endforelse
        </div>
    </section>

    <section class="lic-panel">
        <h2 class="lic-panel__title"><i class="bi bi-clock-history"></i>{{ __('gov_accounts.timeline.title') }}</h2>
        <ol class="lic-timeline">
            @forelse($accountRequest->timeline->sortByDesc('id') as $event)
                <li class="lic-timeline__item">
                    <span class="lic-timeline__dot"></span>
                    <div class="lic-timeline__head">
                        <strong class="lic-timeline__title">{{ __('gov_accounts.timeline.events.'.$event->event_type) }}</strong>
                        <time class="lic-timeline__date">{{ $dateOf($event->date) }}</time>
                    </div>
                    @if($event->notice)<div class="lic-timeline__body">{{ $event->notice }}</div>@endif
                </li>
            @empty
                <li class="lic-empty">{{ __('gov_accounts.requests.empty_timeline') }}</li>
            @endforelse
        </ol>
    </section>

    @if($hasOperations)
        <div class="lic-operation-sources lic-no-print" hidden>
            @if($canSubmit)
                <form method="POST" action="{{ route('modules.gov-accounts.requests.submit', $accountRequest) }}" data-license-operation-form="govRequestOperationSubmit">
                    @csrf
                    <input type="hidden" name="_operation" value="submit">
                    <label class="lic-checkbox"><input type="checkbox" name="manager_undertaking" value="1" required><span>{{ __('gov_accounts.undertakings.manager_text') }}</span></label>
                    <button class="lic-btn lic-btn--primary mt-3" type="submit">{{ __('gov_accounts.actions.submit') }}</button>
                </form>
            @endif
            @if($canResubmit)
                <form method="POST" action="{{ route('modules.gov-accounts.requests.resubmit', $accountRequest) }}" data-license-operation-form="govRequestOperationResubmit">
                    @csrf
                    <input type="hidden" name="_operation" value="resubmit">
                    <div class="lic-field"><label for="gov_request_response">{{ __('gov_accounts.fields.response') }}</label><textarea id="gov_request_response" name="response" class="form-control" required>{{ old('response') }}</textarea></div>
                    <button class="lic-btn lic-btn--primary mt-3" type="submit">{{ __('gov_accounts.actions.resubmit') }}</button>
                </form>
            @endif
            @if($canReview)
                <form method="POST" action="{{ route('modules.gov-accounts.requests.approve', $accountRequest) }}" data-license-operation-form="govRequestOperationApprove">
                    @csrf
                    <input type="hidden" name="_operation" value="approve">
                    <div class="lic-field"><label for="gov_request_approve_notes">{{ __('gov_accounts.fields.notes') }}</label><textarea id="gov_request_approve_notes" name="notes" class="form-control">{{ old('notes') }}</textarea></div>
                    <button class="lic-btn lic-btn--primary mt-3" type="submit">{{ __('gov_accounts.actions.approve') }}</button>
                </form>
                <form method="POST" action="{{ route('modules.gov-accounts.requests.reject', $accountRequest) }}" data-license-operation-form="govRequestOperationReject">
                    @csrf
                    <input type="hidden" name="_operation" value="reject">
                    <div class="lic-field"><label for="gov_request_reason">{{ __('gov_accounts.fields.rejection_reason') }}</label><textarea id="gov_request_reason" name="reason" class="form-control" required>{{ old('reason') }}</textarea></div>
                    <button class="lic-btn mt-3" type="submit">{{ __('gov_accounts.actions.reject') }}</button>
                </form>
            @endif
            @if($canAuthority)
                <form method="POST" action="{{ route('modules.gov-accounts.requests.authority', $accountRequest) }}" data-license-operation-form="govRequestOperationAuthority">
                    @csrf
                    <input type="hidden" name="_operation" value="authority">
                    <div class="lic-field mb-2"><label for="gov_request_authority_date">{{ __('gov_accounts.fields.event_date') }}</label><input id="gov_request_authority_date" type="date" name="authority_submitted_at" value="{{ old('authority_submitted_at', now()->toDateString()) }}" required class="form-control"></div>
                    <div class="lic-field mb-2"><label for="gov_request_authority_ref">{{ __('gov_accounts.fields.reference_no') }}</label><input id="gov_request_authority_ref" name="authority_reference" class="form-control" value="{{ old('authority_reference') }}"></div>
                    <div class="lic-field"><label for="gov_request_authority_notes">{{ __('gov_accounts.fields.notes') }}</label><textarea id="gov_request_authority_notes" name="notes" class="form-control">{{ old('notes') }}</textarea></div>
                    <button class="lic-btn lic-btn--primary mt-3" type="submit">{{ __('gov_accounts.actions.mark_authority') }}</button>
                </form>
            @endif
            @if($canComplete)
                <form method="POST" action="{{ route('modules.gov-accounts.requests.complete', $accountRequest) }}" data-license-operation-form="govRequestOperationComplete">
                    @csrf
                    <input type="hidden" name="_operation" value="complete">
                    @if($accountRequest->type === 'create')
                        <div class="lic-form-grid">
                            <div class="lic-field"><label for="gov_request_username">{{ __('gov_accounts.fields.username') }}</label><input id="gov_request_username" name="username" class="form-control" required value="{{ old('username') }}"></div>
                            <div class="lic-field"><label for="gov_request_login_url">{{ __('gov_accounts.fields.login_url') }}</label><input id="gov_request_login_url" name="login_url" type="url" class="form-control" value="{{ old('login_url') }}"></div>
                            <div class="lic-field"><label for="gov_request_complete_ref">{{ __('gov_accounts.fields.reference_no') }}</label><input id="gov_request_complete_ref" name="reference_no" class="form-control" value="{{ old('reference_no') }}"></div>
                            <div class="lic-field"><label for="gov_request_role">{{ __('gov_accounts.fields.role') }}</label><select id="gov_request_role" name="role_id" class="form-select" required>@foreach($roles as $role)<option value="{{ $role->id }}" @selected((string) old('role_id', $accountRequest->role_id) === (string) $role->id)>{{ $role->localizedName() }}</option>@endforeach</select></div>
                            <div class="lic-field"><label for="gov_request_account_created">{{ __('gov_accounts.export.created_at') }}</label><input id="gov_request_account_created" type="date" name="account_created_at" value="{{ old('account_created_at', now()->toDateString()) }}" required class="form-control"></div>
                        </div>
                    @else
                        <p class="lic-help">{{ __('gov_accounts.lifecycle.confirm_complete') }}</p>
                    @endif
                    <button class="lic-btn lic-btn--primary mt-3" type="submit">{{ __('gov_accounts.actions.complete') }}</button>
                </form>
            @endif
        </div>
        @php
            $govOperationMap = ['submit' => 'govRequestOperationSubmit', 'resubmit' => 'govRequestOperationResubmit', 'approve' => 'govRequestOperationApprove', 'reject' => 'govRequestOperationReject', 'authority' => 'govRequestOperationAuthority', 'complete' => 'govRequestOperationComplete'];
            $govOperationModal = $govOperationMap[old('_operation')] ?? null;
        @endphp
        @if($govOperationModal)<span hidden data-license-open-operation="{{ $govOperationModal }}"></span>@endif
    @endif
</div>
@endsection

@if($hasOperations)
@push('modals')
    @if($canSubmit)@include('licenses.partials.operation-modal', ['id' => 'govRequestOperationSubmit', 'title' => __('gov_accounts.actions.submit'), 'icon' => 'bi-send', 'size' => 'modal-md'])@endif
    @if($canResubmit)@include('licenses.partials.operation-modal', ['id' => 'govRequestOperationResubmit', 'title' => __('gov_accounts.actions.resubmit'), 'icon' => 'bi-arrow-repeat', 'size' => 'modal-md'])@endif
    @if($canReview)
        @include('licenses.partials.operation-modal', ['id' => 'govRequestOperationApprove', 'title' => __('gov_accounts.actions.approve'), 'icon' => 'bi-check2-circle', 'size' => 'modal-md'])
        @include('licenses.partials.operation-modal', ['id' => 'govRequestOperationReject', 'title' => __('gov_accounts.actions.reject'), 'icon' => 'bi-x-circle', 'size' => 'modal-md'])
    @endif
    @if($canAuthority)@include('licenses.partials.operation-modal', ['id' => 'govRequestOperationAuthority', 'title' => __('gov_accounts.actions.mark_authority'), 'icon' => 'bi-box-arrow-up-right', 'size' => 'modal-md'])@endif
    @if($canComplete)@include('licenses.partials.operation-modal', ['id' => 'govRequestOperationComplete', 'title' => __('gov_accounts.actions.complete'), 'icon' => 'bi-patch-check', 'size' => 'modal-lg'])@endif
@endpush
@endif

@push('scripts')
<script src="{{ asset('js/hm-licenses.js') }}?v={{ filemtime(public_path('js/hm-licenses.js')) }}"></script>
@endpush
