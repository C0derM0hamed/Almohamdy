@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-work-absence-notification.css') }}?v={{ filemtime(public_path('css/hm-work-absence-notification.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-detail-stat-cards.css') }}?v={{ filemtime(public_path('css/hm-detail-stat-cards.css')) }}" rel="stylesheet">
@endpush

@section('title', __('work_absence_notification.notification_details'))

@section('sidebar_heading', __('work_absence_notification.title'))
@section('sidebar_subheading', __('work_absence_notification.service_subtitle'))

@section('content')
    @php
        $isRtl = app()->getLocale() === 'ar';
        $workflowStatus = $notification->workflowStatusKey();
        $employeeName = $notification->employeeDisplayName();
        $employeeInitials = '—';

        if ($notification->employee) {
            $first = mb_substr(trim((string) $notification->employee->hr_first_name), 0, 1);
            $last = mb_substr(trim((string) $notification->employee->hr_last_name), 0, 1);
            $employeeInitials = strtoupper($first.$last) ?: '#';
        }

        $absenceReason = trim((string) $notification->absence_reason);
        $medicalAuthority = trim((string) $notification->medical_authority);
        $relationship = trim((string) $notification->relationship);
        $deceasedRelationship = trim((string) $notification->deceased_relationship);
        $smsToken = trim((string) $notification->sms_tocken);
        $hasSupplementary = $absenceReason !== ''
            || $medicalAuthority !== ''
            || $relationship !== ''
            || $deceasedRelationship !== '';
    @endphp

    <div class="hm-hs hm-wan hm-wan--detail {{ $isRtl ? 'hm-wan--rtl' : '' }}">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('work_absence_notification.dashboard'), 'url' => route('modules.work-absence.dashboard')],
                ['label' => __('work_absence_notification.notifications_list'), 'url' => route('modules.work-absence.notifications.index')],
                ['label' => '#'.$notification->id, 'chip' => true],
            ],
        ])

        @if (session('success'))
            <div class="hm-alert-success wan-detail-alert">{{ session('success') }}</div>
        @endif

        <header class="wan-detail-head">
            <div class="wan-detail-head__copy">
                <h1>{{ __('work_absence_notification.notification_details') }}</h1>
                <p>{{ __('work_absence_notification.detail_subtitle') }}</p>
                <div class="wan-detail-head__badges">
                    <span class="wan-detail-no">#{{ $notification->id }}</span>
                    <span class="hm-wan-status-badge hm-wan-status-badge--{{ $workflowStatus }}">
                        {{ $notification->workflowStatusLabel() }}
                    </span>
                </div>
            </div>
            <div class="wan-detail-head__art" aria-hidden="true"></div>
        </header>

        <div class="wan-summary-grid hm-detail-stats hm-detail-stats--four" aria-label="{{ __('work_absence_notification.notification_details') }}">
            <article class="wan-summary-card hm-detail-stat hm-detail-stat--primary">
                <span class="hm-detail-stat__icon" aria-hidden="true"><i class="bi bi-file-earmark-medical"></i></span>
                <span class="hm-detail-stat__copy">
                    <small class="hm-detail-stat__label">{{ __('work_absence_notification.fields.notification_type') }}</small>
                    <strong class="hm-detail-stat__value">{{ $notification->notificationTypeLabel() }}</strong>
                    <span class="hm-detail-stat__meta">#{{ $notification->id }}</span>
                </span>
            </article>
            <article class="wan-summary-card hm-detail-stat hm-detail-stat--dark">
                <span class="hm-detail-stat__icon" aria-hidden="true"><i class="bi bi-calendar-range"></i></span>
                <span class="hm-detail-stat__copy">
                    <small class="hm-detail-stat__label">{{ __('work_absence_notification.summary.period') }}</small>
                    <strong class="hm-detail-stat__value">{{ $notification->formattedBeginDate() }} → {{ $notification->formattedEndDate() }}</strong>
                    <span class="hm-detail-stat__meta">{{ $notification->workflowStatusLabel() }}</span>
                </span>
            </article>
            <article class="wan-summary-card hm-detail-stat hm-detail-stat--primary">
                <span class="hm-detail-stat__icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                <span class="hm-detail-stat__copy">
                    <small class="hm-detail-stat__label">{{ __('work_absence_notification.fields.absence_days') }}</small>
                    <strong class="hm-detail-stat__value">{{ $notification->absence_days ?? '—' }}</strong>
                    <span class="hm-detail-stat__meta">{{ __('work_absence_notification.notification_details') }}</span>
                </span>
            </article>
            <article class="wan-summary-card hm-detail-stat hm-detail-stat--dark">
                <span class="hm-detail-stat__icon" aria-hidden="true"><i class="bi bi-calendar-check"></i></span>
                <span class="hm-detail-stat__copy">
                    <small class="hm-detail-stat__label">{{ __('work_absence_notification.fields.created_date') }}</small>
                    <strong class="hm-detail-stat__value">{{ $notification->formattedCreatedDate() }}</strong>
                    <span class="hm-detail-stat__meta">{{ $employeeName }}</span>
                </span>
            </article>
        </div>

        <section class="wan-detail-grid">
            <article class="wan-info-card">
                <div class="wan-info-card__head">
                    <span class="wan-info-card__icon wan-info-card__icon--employee" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                    <h2>{{ __('work_absence_notification.sections.employee_info') }}</h2>
                </div>

                <div class="wan-employee-profile">
                    <span class="wan-employee-profile__avatar" aria-hidden="true">{{ $employeeInitials }}</span>
                    <div class="wan-employee-profile__copy">
                        <strong>{{ $employeeName }}</strong>
                        @if ($notification->employee && trim((string) $notification->employee->job_title) !== '')
                            <span>{{ $notification->employee->job_title }}</span>
                        @elseif (! $notification->employee)
                            <span>{{ __('work_absence_notification.employee_not_linked') }}</span>
                        @endif
                    </div>
                </div>

                <div class="wan-info-list">
                    <div class="wan-info-row">
                        <span class="wan-info-row__label">{{ __('work_absence_notification.fields.employee') }}</span>
                        <span class="wan-info-row__value">{{ $employeeName }}</span>
                    </div>
                    @if ($notification->employee)
                        <div class="wan-info-row">
                            <span class="wan-info-row__label">{{ __('work_absence_notification.fields.username') }}</span>
                            <span class="wan-info-row__value">{{ $notification->employee->hr_username ?: '—' }}</span>
                        </div>
                        <div class="wan-info-row">
                            <span class="wan-info-row__label">{{ __('work_absence_notification.fields.email') }}</span>
                            <span class="wan-info-row__value">{{ $notification->employee->hr_email_address ?: '—' }}</span>
                        </div>
                        <div class="wan-info-row">
                            <span class="wan-info-row__label">{{ __('work_absence_notification.fields.mobile') }}</span>
                            <span class="wan-info-row__value">{{ $notification->employee->mobile ?: '—' }}</span>
                        </div>
                        <div class="wan-info-row">
                            <span class="wan-info-row__label">{{ __('work_absence_notification.fields.job_title') }}</span>
                            <span class="wan-info-row__value">{{ $notification->employee->job_title ?? '—' }}</span>
                        </div>
                    @endif
                </div>
            </article>

            <article class="wan-info-card">
                <div class="wan-info-card__head">
                    <span class="wan-info-card__icon wan-info-card__icon--request" aria-hidden="true"><i class="bi bi-file-earmark-medical"></i></span>
                    <h2>{{ __('work_absence_notification.sections.notification_info') }}</h2>
                </div>
                <div class="wan-info-list">
                    <div class="wan-info-row">
                        <span class="wan-info-row__label">{{ __('work_absence_notification.fields.request_id') }}</span>
                        <span class="wan-info-row__value">#{{ $notification->id }}</span>
                    </div>
                    <div class="wan-info-row">
                        <span class="wan-info-row__label">{{ __('work_absence_notification.fields.notification_type') }}</span>
                        <span class="wan-info-row__value">{{ $notification->notificationTypeLabel() }}</span>
                    </div>
                    <div class="wan-info-row">
                        <span class="wan-info-row__label">{{ __('work_absence_notification.fields.begin_date') }}</span>
                        <span class="wan-info-row__value">{{ $notification->formattedBeginDate() }}</span>
                    </div>
                    <div class="wan-info-row">
                        <span class="wan-info-row__label">{{ __('work_absence_notification.fields.end_date') }}</span>
                        <span class="wan-info-row__value">{{ $notification->formattedEndDate() }}</span>
                    </div>
                    <div class="wan-info-row">
                        <span class="wan-info-row__label">{{ __('work_absence_notification.fields.absence_days') }}</span>
                        <span class="wan-info-row__value">{{ $notification->absence_days ?? '—' }}</span>
                    </div>
                    <div class="wan-info-row">
                        <span class="wan-info-row__label">{{ __('work_absence_notification.fields.created_date') }}</span>
                        <span class="wan-info-row__value">{{ $notification->formattedCreatedDate() }}</span>
                    </div>
                </div>
            </article>
        </section>

        @if ($hasSupplementary || $smsToken !== '')
            <section class="wan-detail-supplementary">
                @if ($hasSupplementary)
                    <article class="wan-info-card wan-info-card--supplementary">
                        <div class="wan-info-card__head">
                            <span class="wan-info-card__icon wan-info-card__icon--supplementary" aria-hidden="true"><i class="bi bi-card-text"></i></span>
                            <h2>{{ __('work_absence_notification.sections.supplementary_info') }}</h2>
                        </div>
                        <div class="wan-info-list">
                            @if ($absenceReason !== '')
                                <div class="wan-info-row wan-info-row--stacked">
                                    <span class="wan-info-row__label">{{ __('work_absence_notification.fields.absence_reason') }}</span>
                                    <span class="wan-info-row__value wan-info-row__value--block">{{ $absenceReason }}</span>
                                </div>
                            @endif
                            @if ($medicalAuthority !== '')
                                <div class="wan-info-row">
                                    <span class="wan-info-row__label">{{ __('work_absence_notification.fields.medical_authority') }}</span>
                                    <span class="wan-info-row__value">{{ $medicalAuthority }}</span>
                                </div>
                            @endif
                            @if ($relationship !== '')
                                <div class="wan-info-row">
                                    <span class="wan-info-row__label">{{ __('work_absence_notification.fields.relationship') }}</span>
                                    <span class="wan-info-row__value">{{ $relationship }}</span>
                                </div>
                            @endif
                            @if ($deceasedRelationship !== '')
                                <div class="wan-info-row">
                                    <span class="wan-info-row__label">{{ __('work_absence_notification.fields.deceased_relationship') }}</span>
                                    <span class="wan-info-row__value">{{ $deceasedRelationship }}</span>
                                </div>
                            @endif
                        </div>
                    </article>
                @endif

                @if ($smsToken !== '')
                    <article class="wan-info-card wan-info-card--technical">
                        <div class="wan-info-card__head">
                            <span class="wan-info-card__icon wan-info-card__icon--technical" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
                            <h2>{{ __('work_absence_notification.sections.technical_details') }}</h2>
                        </div>
                        <div class="wan-info-list">
                            <div class="wan-info-row wan-info-row--stacked">
                                <span class="wan-info-row__label">{{ __('work_absence_notification.fields.sms_token') }}</span>
                                <span class="wan-info-row__value wan-info-row__value--block wan-info-row__value--mono">{{ $smsToken }}</span>
                            </div>
                        </div>
                    </article>
                @endif
            </section>
        @endif

    <div class="wan-detail-stack">
    <div class="hm-wan-detail-card wan-info-card wan-info-card--panel">
        <div class="wan-info-card__head">
            <span class="wan-info-card__icon wan-info-card__icon--history" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
            <h2>{{ __('work_absence_notification.sections.status_history') }}</h2>
        </div>
        @if (count($statusHistory) > 0)
            <ul class="hm-history-list">
                @foreach ($statusHistory as $event)
                    @include('work-absence-notification.partials.status-history-item', ['event' => $event])
                @endforeach
            </ul>
        @else
            <p class="hm-wan-detail-card__empty">{{ __('work_absence_notification.no_history') }}</p>
        @endif
    </div>

    @if ($workflowStatus === 'pending')
        @can('work_absence_notification.process')
        <div class="hm-wan-detail-card hm-wan-detail-card--process wan-info-card wan-info-card--panel">
            <div class="wan-info-card__head">
                <span class="wan-info-card__icon wan-info-card__icon--process" aria-hidden="true"><i class="bi bi-clipboard-check"></i></span>
                <h2>{{ __('work_absence_notification.sections.hr_processing') }}</h2>
            </div>
            <form method="POST" action="{{ route('modules.work-absence.notifications.process', $notification->id) }}" class="hm-wan-process-form">
                @csrf
                <div class="hm-wan-process-form__field">
                    <label for="action_type" class="form-label">{{ __('work_absence_notification.fields.action_type') }}</label>
                    <select
                        name="action_type"
                        id="action_type"
                        class="form-select @error('action_type') is-invalid @enderror"
                        required
                    >
                        <option value="">{{ __('work_absence_notification.processing.action_type_placeholder') }}</option>
                        @foreach ($actionTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) old('action_type') === (string) $type->id)>
                                {{ $type->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                    @error('action_type')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn hm-btn hm-btn--primary">
                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                    {{ __('work_absence_notification.processing.submit') }}
                </button>
            </form>
        </div>
        @endcan
    @endif

    @if ($workflowStatus === 'action_taken')
        <div class="hm-wan-detail-card wan-info-card wan-info-card--panel">
            <div class="wan-info-card__head">
                <span class="wan-info-card__icon wan-info-card__icon--process" aria-hidden="true"><i class="bi bi-check2-square"></i></span>
                <h2>{{ __('work_absence_notification.sections.action_details') }}</h2>
            </div>
            <div class="wan-info-list">
                <div class="wan-info-row">
                    <span class="wan-info-row__label">{{ __('work_absence_notification.fields.action_type') }}</span>
                    <span class="wan-info-row__value">{{ $notification->actionTypeLabel() }}</span>
                </div>
                <div class="wan-info-row">
                    <span class="wan-info-row__label">{{ __('work_absence_notification.fields.action_date') }}</span>
                    <span class="wan-info-row__value">{{ $notification->formattedActionDate() }}</span>
                </div>
                <div class="wan-info-row">
                    <span class="wan-info-row__label">{{ __('work_absence_notification.fields.action_by') }}</span>
                    <span class="wan-info-row__value">
                        @if ($notification->actionByUser)
                            {{ $notification->actionByUser->displayName() }}
                        @else
                            —
                        @endif
                    </span>
                </div>
            </div>
        </div>

        @if ($canCreateMemo)
            @can('work_absence_notification.process')
            @php
                $defaultBeginDate = trim((string) $notification->begin_date);
                $defaultEndDate = trim((string) $notification->end_date);
                $selectedRecipients = collect(old('recipient_ids', []))->map(fn ($id) => (int) $id)->all();
            @endphp
            <div class="hm-wan-detail-card hm-wan-detail-card--process wan-info-card wan-info-card--panel">
                <div class="wan-info-card__head">
                    <span class="wan-info-card__icon wan-info-card__icon--memo" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                    <h2>{{ __('work_absence_notification.sections.memo_creation') }}</h2>
                </div>
                <form
                    method="POST"
                    action="{{ route('modules.work-absence.notifications.memos.store', $notification->id) }}"
                    class="hm-wan-memo-form"
                >
                    @csrf
                    @error('notification')
                        <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                    @enderror

                    <div class="hm-wan-memo-form__field">
                        <label for="memo_type" class="form-label">{{ __('work_absence_notification.memo.fields.memo_type') }}</label>
                        <select
                            name="memo_type"
                            id="memo_type"
                            class="form-select @error('memo_type') is-invalid @enderror"
                            required
                        >
                            <option value="">{{ __('work_absence_notification.memo.memo_type_placeholder') }}</option>
                            @foreach ($memoTypes as $type)
                                <option value="{{ $type->id }}" @selected((string) old('memo_type') === (string) $type->id)>
                                    {{ $type->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('memo_type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="hm-wan-memo-form__field">
                        <span class="form-label d-block">{{ __('work_absence_notification.memo.fields.recipient_ids') }}</span>
                        <p class="hm-wan-memo-form__hint">{{ __('work_absence_notification.memo.recipients_hint') }}</p>
                        <div class="hm-wan-memo-recipients @error('recipient_ids') is-invalid @enderror @error('recipient_ids.*') is-invalid @enderror">
                            @foreach ($memoRecipients as $recipient)
                                <label class="hm-wan-memo-recipients__item">
                                    <input
                                        type="checkbox"
                                        name="recipient_ids[]"
                                        value="{{ $recipient->hr_id }}"
                                        @checked(in_array((int) $recipient->hr_id, $selectedRecipients, true))
                                    >
                                    <span class="hm-wan-memo-recipients__name">{{ $recipient->displayName() }}</span>
                                    @if (trim((string) $recipient->job_title) !== '')
                                        <span class="hm-wan-memo-recipients__meta">{{ $recipient->job_title }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        @error('recipient_ids')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('recipient_ids.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="hm-wan-memo-form__dates">
                        <div class="hm-wan-memo-form__field">
                            <label for="memo_begin_date" class="form-label">{{ __('work_absence_notification.memo.fields.begin_date') }}</label>
                            <input
                                type="date"
                                name="begin_date"
                                id="memo_begin_date"
                                class="form-control @error('begin_date') is-invalid @enderror"
                                value="{{ old('begin_date', $defaultBeginDate !== '' ? $defaultBeginDate : '') }}"
                            >
                            @error('begin_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="hm-wan-memo-form__field">
                            <label for="memo_end_date" class="form-label">{{ __('work_absence_notification.memo.fields.end_date') }}</label>
                            <input
                                type="date"
                                name="end_date"
                                id="memo_end_date"
                                class="form-control @error('end_date') is-invalid @enderror"
                                value="{{ old('end_date', $defaultEndDate !== '' ? $defaultEndDate : '') }}"
                            >
                            @error('end_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="hm-wan-memo-form__field">
                        <label for="memo_notes" class="form-label">{{ __('work_absence_notification.memo.fields.notes') }}</label>
                        <textarea
                            name="notes"
                            id="memo_notes"
                            rows="3"
                            maxlength="20"
                            class="form-control @error('notes') is-invalid @enderror"
                        >{{ old('notes') }}</textarea>
                        <p class="hm-wan-memo-form__hint">{{ __('work_absence_notification.memo.notes_hint', ['max' => 20]) }}</p>
                        @error('notes')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn hm-btn hm-btn--primary">
                        <i class="bi bi-file-earmark-plus" aria-hidden="true"></i>
                        {{ __('work_absence_notification.memo.submit') }}
                    </button>
                </form>
            </div>
            @endcan
        @endif

        @can('work_absence_notification.activate')
        <div class="hm-wan-detail-card hm-wan-detail-card--process wan-info-card wan-info-card--panel">
            <div class="wan-info-card__head">
                <span class="wan-info-card__icon wan-info-card__icon--activate" aria-hidden="true"><i class="bi bi-lightning-charge"></i></span>
                <h2>{{ __('work_absence_notification.sections.activation') }}</h2>
            </div>
            <form method="POST" action="{{ route('modules.work-absence.notifications.activate', $notification->id) }}" class="hm-wan-process-form">
                @csrf
                @error('notification')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <button type="submit" class="btn hm-btn hm-btn--primary">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    {{ __('work_absence_notification.activation.submit') }}
                </button>
            </form>
        </div>
        @endcan
    @endif

    @if ($workflowStatus === 'activated')
        <div class="hm-wan-detail-card wan-info-card wan-info-card--panel">
            <div class="wan-info-card__head">
                <span class="wan-info-card__icon wan-info-card__icon--activate" aria-hidden="true"><i class="bi bi-check-circle"></i></span>
                <h2>{{ __('work_absence_notification.sections.activation_info') }}</h2>
            </div>
            <div class="wan-info-list">
                <div class="wan-info-row">
                    <span class="wan-info-row__label">{{ __('work_absence_notification.fields.workflow_status') }}</span>
                    <span class="wan-info-row__value">
                        <span class="hm-wan-status-badge hm-wan-status-badge--activated">
                            {{ $notification->workflowStatusLabel() }}
                        </span>
                    </span>
                </div>
                <div class="wan-info-row">
                    <span class="wan-info-row__label">{{ __('work_absence_notification.fields.activated_by') }}</span>
                    <span class="wan-info-row__value">
                        @if ($notification->activatedByUser)
                            {{ $notification->activatedByUser->displayName() }}
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="wan-info-row">
                    <span class="wan-info-row__label">{{ __('work_absence_notification.fields.activated_at') }}</span>
                    <span class="wan-info-row__value">{{ $notification->formattedActivatedAt() }}</span>
                </div>
            </div>
        </div>
    @endif

    <div class="hm-wan-detail-card wan-info-card wan-info-card--panel">
        <div class="wan-info-card__head">
            <span class="wan-info-card__icon wan-info-card__icon--memo" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
            <h2>{{ __('work_absence_notification.sections.memo_history') }}</h2>
        </div>
        @if ($notification->memos->count() > 0)
            <div class="hm-wan-table-wrap">
                <table class="hm-wan-table">
                    <thead>
                        <tr>
                            <th>{{ __('work_absence_notification.fields.memo_type') }}</th>
                            <th>{{ __('work_absence_notification.fields.memo_date') }}</th>
                            <th>{{ __('work_absence_notification.fields.begin_date') }}</th>
                            <th>{{ __('work_absence_notification.fields.end_date') }}</th>
                            <th>{{ __('work_absence_notification.fields.memo_recipients') }}</th>
                            <th>{{ __('work_absence_notification.fields.memo_notes') }}</th>
                            <th>{{ __('work_absence_notification.fields.seen_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notification->memos as $memo)
                            @forelse ($memo->recipients as $recipient)
                                <tr>
                                    @if ($loop->first)
                                        <td rowspan="{{ $memo->recipients->count() }}">{{ $memo->memoTypeLabel() }}</td>
                                        <td rowspan="{{ $memo->recipients->count() }}">{{ $memo->formattedDate() }}</td>
                                        <td rowspan="{{ $memo->recipients->count() }}">{{ $memo->formattedBeginDate() }}</td>
                                        <td rowspan="{{ $memo->recipients->count() }}">{{ $memo->formattedEndDate() }}</td>
                                    @endif
                                    <td>{{ $recipient->recipient?->displayName() ?? '#'.$recipient->user_id }}</td>
                                    @if ($loop->first)
                                        <td rowspan="{{ $memo->recipients->count() }}">{{ $memo->notesText() }}</td>
                                    @endif
                                    <td>{{ $recipient->formattedSeenAt() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td>{{ $memo->memoTypeLabel() }}</td>
                                    <td>{{ $memo->formattedDate() }}</td>
                                    <td>{{ $memo->formattedBeginDate() }}</td>
                                    <td>{{ $memo->formattedEndDate() }}</td>
                                    <td>—</td>
                                    <td>{{ $memo->notesText() }}</td>
                                    <td>—</td>
                                </tr>
                            @endforelse
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="hm-wan-detail-card__empty">{{ __('work_absence_notification.no_memo_history') }}</p>
        @endif
    </div>

    @include('work-absence-notification.partials.recipient-tracking', [
        'notification' => $notification,
        'recipientStats' => $recipientStats,
    ])

    <div class="hm-wan-detail-card wan-info-card wan-info-card--panel">
        <div class="wan-info-card__head">
            <span class="wan-info-card__icon wan-info-card__icon--attachment" aria-hidden="true"><i class="bi bi-paperclip"></i></span>
            <h2>{{ __('work_absence_notification.sections.attachment_info') }}</h2>
        </div>
        @if ($notification->hasAttachment())
            <a href="{{ $notification->protectedAttachmentUrl() }}" class="hm-wan-doc-link" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-download" aria-hidden="true"></i>
                {{ __('work_absence_notification.download_attachment') }}
            </a>
            <p class="wan-info-card__meta">{{ basename((string) $notification->sick_leave_file) }}</p>
        @else
            <p class="hm-wan-detail-card__empty">{{ __('work_absence_notification.no_attachment') }}</p>
        @endif
    </div>

    </div>

    <div class="wan-detail-actions">
        <a href="{{ route('modules.work-absence.notifications.index') }}" class="hs-btn hs-btn--primary">
            <i class="bi bi-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
            {{ __('work_absence_notification.back_to_list') }}
        </a>
    </div>
    </div>
@endsection
