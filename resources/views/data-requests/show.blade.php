@extends('layouts.app')

@section('title', __('data_requests.detail'))
@section('sidebar_heading', __('data_requests.title'))
@section('sidebar_subheading', __('data_requests.detail_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inspection-visits.css') }}?v={{ filemtime(public_path('css/hm-inspection-visits.css')) }}" rel="stylesheet">
@endpush

@section('content')
    @php
        $selectedStatus = (int) old('status_id', 0);
        $statusEscalated = 1;
        $statusReturned = 2;
        $statusEntityNotified = 4;
    @endphp
    <div class="hm-gc hm-iv">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.data-requests.index') }}">{{ __('data_requests.list') }}</a>
            <span>/</span>
            <span class="is-chip">{{ $request->displayNumber() }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('data_requests.detail') }}</h1>
                <p>{{ $request->subject() ?: $request->displayNumber() }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="gc-badge" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                <a href="{{ route('modules.data-requests.receipt', $request->id) }}" class="btn btn-outline-primary btn-sm">
                    {{ __('data_requests.actions.receipt') }}
                    <span class="badge text-bg-light ms-1">{{ $recipientsCount }}</span>
                </a>
                @if (in_array((int) $request->status, [2, 6], true))
                    <a href="{{ $departmentReplyUrl }}" class="btn btn-outline-success btn-sm" target="_blank" rel="noopener">
                        {{ __('data_requests.department_reply.open_link') }}
                    </a>
                @endif
                <a href="{{ route('modules.data-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('data_requests.back_to_list') }}
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="gc-panel">
            <div class="gc-detail-grid">
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.table.request_number') }}</span>
                    <span class="gc-detail-item__value">{{ $request->displayNumber() }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.entity') }}</span>
                    <span class="gc-detail-item__value">{{ $request->entity?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.data_type') }}</span>
                    <span class="gc-detail-item__value">{{ $request->dataType?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.request_date') }}</span>
                    <span class="gc-detail-item__value">{{ $request->date ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.receipt_date') }}</span>
                    <span class="gc-detail-item__value">{{ $request->Date_receipt ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.receiving_method') }}</span>
                    <span class="gc-detail-item__value">{{ $request->receivingMethod?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.branch') }}</span>
                    <span class="gc-detail-item__value">{{ $request->branch?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.section') }}</span>
                    <span class="gc-detail-item__value">{{ $request->section?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.deadline') }}</span>
                    <span class="gc-detail-item__value">{{ $request->Data_delivery ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.reminder_at') }}</span>
                    <span class="gc-detail-item__value">{{ $request->reminderAt() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item" style="grid-column: 1 / -1;">
                    <span class="gc-detail-item__label">{{ __('data_requests.fields.subject') }}</span>
                    <span class="gc-detail-item__value">{{ $request->subject() ?: '—' }}</span>
                </div>
                @if (filled($request->becuse))
                    <div class="gc-detail-item" style="grid-column: 1 / -1;">
                        <span class="gc-detail-item__label">{{ __('data_requests.fields.reason') }}</span>
                        <span class="gc-detail-item__value">{{ $request->becuse }}</span>
                    </div>
                @endif
            </div>
        </section>

        <section class="gc-panel mt-3" id="status-update">
            <h2 class="iv-section-title">{{ __('data_requests.status_form.title') }}</h2>
            <p class="text-muted mb-3">{{ __('data_requests.status_form.subtitle') }}</p>

            @if ($updatableStatuses->isEmpty())
                <div class="gc-empty">{{ __('data_requests.status_form.empty_statuses') }}</div>
            @else
                <form method="POST" action="{{ route('modules.data-requests.status', $request->id) }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="gc-form-grid">
                        <div class="gc-field">
                            <label for="status_id">{{ __('data_requests.status_form.status') }}</label>
                            <select id="status_id" name="status_id" class="form-select @error('status_id') is-invalid @enderror" required>
                                <option value="">—</option>
                                @foreach ($updatableStatuses as $status)
                                    <option value="{{ $status->id }}" @selected($selectedStatus === (int) $status->id)>
                                        {{ $status->localizedName() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field gc-span-2" id="reasonWrap" hidden>
                            <label for="reason">{{ __('data_requests.status_form.reason') }}</label>
                            <textarea id="reason" name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
                            @error('reason') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="noticeNameWrap" hidden>
                            <label for="notice_name">{{ __('data_requests.status_form.notice_name') }}</label>
                            <input id="notice_name" type="text" name="notice_name" value="{{ old('notice_name') }}" class="form-control" maxlength="200">
                        </div>

                        <div class="gc-field" id="noticeFileWrap" hidden>
                            <label for="notice_file">{{ __('data_requests.status_form.notice_file') }}</label>
                            <input id="notice_file" type="file" name="notice_file" class="form-control @error('notice_file') is-invalid @enderror">
                            @error('notice_file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">{{ __('data_requests.status_form.submit') }}</button>
                    </div>
                </form>
            @endif
        </section>

        <section class="gc-panel mt-3">
            <h2 class="iv-section-title">{{ __('data_requests.fields.attachments') }}</h2>
            @if ($request->mailFiles->isEmpty())
                <div class="gc-empty">{{ __('data_requests.no_attachment') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('data_requests.fields.attachment_name') }}</th>
                                <th>{{ __('data_requests.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($request->mailFiles as $file)
                                <tr>
                                    <td>{{ $file->displayLabel() }}</td>
                                    <td>
                                        @if (! empty($attachmentUrls[$file->id]))
                                            <a href="{{ $attachmentUrls[$file->id] }}" target="_blank" rel="noopener" class="gc-link-count">
                                                {{ __('data_requests.open_attachment') }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="gc-panel mt-3">
            <h2 class="iv-section-title">{{ __('data_requests.notices.title') }}</h2>
            @if ($request->answerFiles->isEmpty())
                <div class="gc-empty">{{ __('data_requests.notices.empty') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('data_requests.fields.attachment_name') }}</th>
                                <th>{{ __('data_requests.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($request->answerFiles as $file)
                                <tr>
                                    <td>{{ $file->displayLabel() }}</td>
                                    <td>
                                        @if (! empty($noticeUrls[$file->id]))
                                            <a href="{{ $noticeUrls[$file->id] }}" target="_blank" rel="noopener" class="gc-link-count">
                                                {{ __('data_requests.open_attachment') }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="gc-panel mt-3">
            <h2 class="iv-section-title">{{ __('data_requests.recipients.title') }}</h2>
            @if ($request->views->isEmpty())
                <div class="gc-empty">{{ __('data_requests.recipients.empty') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('data_requests.recipients.name') }}</th>
                                <th>{{ __('data_requests.recipients.department') }}</th>
                                <th>{{ __('data_requests.recipients.viewing_status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($request->views as $view)
                                @php $admin = $view->administrator; @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $admin?->displayName() ?: '—' }}</strong>
                                        <div class="text-muted small">{{ $admin?->email ?: '—' }}</div>
                                    </td>
                                    <td>{{ $admin?->section?->localizedName() ?: '—' }}</td>
                                    <td>
                                        @if ($view->hasBeenViewed())
                                            <span class="gc-badge" style="background:#15803d;">{{ __('data_requests.recipients.viewed') }}</span>
                                        @else
                                            <span class="gc-badge" style="background:#b45309;">{{ __('data_requests.recipients.not_viewed') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="gc-panel mt-3">
            <h2 class="iv-section-title">{{ __('data_requests.timeline.title') }}</h2>
            @if ($request->timelineEntries->isEmpty())
                <div class="gc-empty">{{ __('data_requests.timeline.empty') }}</div>
            @else
                <ul class="iv-timeline">
                    @foreach ($request->timelineEntries->sortByDesc('id') as $entry)
                        <li>
                            <span class="gc-badge" style="background-color: {{ $entry->statusRecord?->badgeColor() ?: '#64748b' }};">
                                {{ $entry->statusRecord?->localizedName() ?: __('data_requests.status_unknown') }}
                            </span>
                            <span class="iv-timeline__date">{{ $entry->create_at ?: '—' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var statusSelect = document.getElementById('status_id');
            var reasonWrap = document.getElementById('reasonWrap');
            var noticeFileWrap = document.getElementById('noticeFileWrap');
            var noticeNameWrap = document.getElementById('noticeNameWrap');
            if (!statusSelect || !reasonWrap || !noticeFileWrap || !noticeNameWrap) return;

            var STATUS_ESCALATED = {{ $statusEscalated }};
            var STATUS_RETURNED = {{ $statusReturned }};
            var STATUS_ENTITY_NOTIFIED = {{ $statusEntityNotified }};

            function sync() {
                var value = parseInt(statusSelect.value || '0', 10);
                reasonWrap.hidden = !(value === STATUS_ESCALATED || value === STATUS_RETURNED);
                noticeFileWrap.hidden = value !== STATUS_ENTITY_NOTIFIED;
                noticeNameWrap.hidden = value !== STATUS_ENTITY_NOTIFIED;
            }

            statusSelect.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush
