@extends('layouts.app')

@section('title', __('inspection_visits.detail'))
@section('sidebar_heading', __('inspection_visits.title'))
@section('sidebar_subheading', __('inspection_visits.detail_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inspection-visits.css') }}?v={{ filemtime(public_path('css/hm-inspection-visits.css')) }}" rel="stylesheet">
@endpush

@section('content')
    @php
        $selectedStatus = (int) old('status_id', 0);
        $statusEntityNotified = 6;
        $statusReturned = 7;
    @endphp
    <div class="hm-gc hm-iv">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.inspection-visits.index') }}">{{ __('inspection_visits.list') }}</a>
            <span>/</span>
            <span class="is-chip">{{ $visit->displayNumber() }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('inspection_visits.detail') }}</h1>
                <p>{{ $visit->visitNumberRecord?->subject ?: $visit->displayNumber() }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="gc-badge" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                <a href="{{ route('modules.inspection-visits.receipt', $visit->id) }}" class="btn btn-outline-primary btn-sm">
                    {{ __('inspection_visits.actions.receipt') }}
                    <span class="badge text-bg-light ms-1">{{ $recipientsCount }}</span>
                </a>
                <a href="{{ route('modules.inspection-visits.pdf', $visit->id) }}" class="btn btn-outline-primary btn-sm">
                    PDF
                </a>
                @if ($departmentReplyUrl && ((int) $visit->status === 1 || (int) $visit->status === 7))
                    <a href="{{ $departmentReplyUrl }}" class="btn btn-outline-success btn-sm" target="_blank" rel="noopener">
                        {{ (int) $visit->status === 7
                            ? __('inspection_visits.department_reply.open_returned_link')
                            : __('inspection_visits.department_reply.open_link') }}
                    </a>
                @endif
                <a href="{{ route('modules.inspection-visits.index') }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('inspection_visits.back_to_list') }}
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="gc-panel">
            <div class="gc-detail-grid">
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.table.visit_number') }}</span>
                    <span class="gc-detail-item__value">{{ $visit->displayNumber() }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.visit_type') }}</span>
                    <span class="gc-detail-item__value">{{ $visit->visitType?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.visit_date') }}</span>
                    <span class="gc-detail-item__value">{{ optional($visit->visit_date)->format('Y-m-d H:i') ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.reply_time') }}</span>
                    <span class="gc-detail-item__value">{{ optional($visit->reply_time)->format('Y-m-d H:i') ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.authority') }}</span>
                    <span class="gc-detail-item__value">{{ $visit->authority?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.representative_name') }}</span>
                    <span class="gc-detail-item__value">{{ $visit->visitNumberRecord?->representative_name ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.branch') }}</span>
                    <span class="gc-detail-item__value">{{ $visit->branch?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.section') }}</span>
                    <span class="gc-detail-item__value">{{ $visit->section?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.abuses_status') }}</span>
                    <span class="gc-detail-item__value">
                        {{ $visit->visitNumberRecord?->hasViolations() ? __('inspection_visits.fields.yes') : __('inspection_visits.fields.no') }}
                    </span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.notes_status') }}</span>
                    <span class="gc-detail-item__value">
                        {{ $visit->visitNumberRecord?->hasNotes() ? __('inspection_visits.fields.yes') : __('inspection_visits.fields.no') }}
                    </span>
                </div>
                <div class="gc-detail-item" style="grid-column: 1 / -1;">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.subject') }}</span>
                    <span class="gc-detail-item__value">{{ $visit->visitNumberRecord?->subject ?: '—' }}</span>
                </div>
                <div class="gc-detail-item" style="grid-column: 1 / -1;">
                    <span class="gc-detail-item__label">{{ __('inspection_visits.fields.report') }}</span>
                    <span class="gc-detail-item__value">{{ $visit->visitNumberRecord?->report ?: '—' }}</span>
                </div>
            </div>
        </section>

        <section class="gc-panel mt-3" id="status-update">
            <h2 class="iv-section-title">{{ __('inspection_visits.status_form.title') }}</h2>
            <p class="text-muted mb-3">{{ __('inspection_visits.status_form.subtitle') }}</p>

            @if ($updatableStatuses->isEmpty())
                <div class="gc-empty">{{ __('inspection_visits.status_form.empty_statuses') }}</div>
            @else
                <form method="POST" action="{{ route('modules.inspection-visits.status', $visit->id) }}" enctype="multipart/form-data" novalidate>
                    @csrf

                    <div class="gc-form-grid">
                        <div class="gc-field">
                            <label for="status_id">{{ __('inspection_visits.status_form.status') }}</label>
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

                        <div class="gc-field" id="noticeFileWrap" hidden>
                            <label for="notice_file">{{ __('inspection_visits.status_form.notice_file') }}</label>
                            <input id="notice_file" type="file" name="notice_file" class="form-control @error('notice_file') is-invalid @enderror">
                            @error('notice_file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field gc-span-2" id="returnReasonsWrap" hidden>
                            <strong class="d-block mb-2">{{ __('inspection_visits.status_form.return_reasons') }}</strong>
                            @forelse ($visit->findings as $index => $finding)
                                <div class="iv-return-row mb-2">
                                    <input type="hidden" name="returns[{{ $index }}][finding_id]" value="{{ $finding->id }}">
                                    <div class="small fw-semibold mb-1">
                                        {{ $finding->isViolation() ? __('inspection_visits.fields.violation') : __('inspection_visits.fields.note') }}:
                                        {{ $finding->abuse_note_title }}
                                    </div>
                                    <textarea
                                        name="returns[{{ $index }}][reason]"
                                        rows="2"
                                        class="form-control"
                                        placeholder="{{ __('inspection_visits.status_form.reason') }}"
                                    >{{ old('returns.'.$index.'.reason') }}</textarea>
                                </div>
                            @empty
                                <div class="text-muted">{{ __('inspection_visits.findings_list.empty') }}</div>
                            @endforelse
                            @error('returns') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">{{ __('inspection_visits.status_form.submit') }}</button>
                    </div>
                </form>
            @endif
        </section>

        <section class="gc-panel mt-3">
            <h2 class="iv-section-title">{{ __('inspection_visits.findings_list.title') }}</h2>
            @if ($visit->findings->isEmpty())
                <div class="gc-empty">{{ __('inspection_visits.findings_list.empty') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('inspection_visits.fields.finding_type') }}</th>
                                <th>{{ __('inspection_visits.fields.finding_title') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visit->findings as $finding)
                                <tr>
                                    <td>
                                        {{ $finding->isViolation() ? __('inspection_visits.fields.violation') : __('inspection_visits.fields.note') }}
                                    </td>
                                    <td>
                                        {{ $finding->abuse_note_title ?: '—' }}
                                        @if ($finding->uploaded_file)
                                            <a class="gc-link-count ms-2" href="{{ route('modules.inspection-visits.findings.download', [$visit->id, $finding->id]) }}">
                                                {{ __('inspection_visits.attachments.open') }}
                                            </a>
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
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 class="iv-section-title mb-0">{{ __('inspection_visits.attachments.title') }}</h2>
            </div>

            <form method="POST" action="{{ route('modules.inspection-visits.attachments.store', $visit->id) }}" enctype="multipart/form-data" class="iv-upload-form mb-3">
                @csrf
                <div class="gc-form-grid">
                    <div class="gc-field">
                        <label for="attachment_name">{{ __('inspection_visits.attachments.name') }}</label>
                        <input id="attachment_name" type="text" name="attachment_name" value="{{ old('attachment_name') }}" class="form-control @error('attachment_name') is-invalid @enderror" maxlength="220">
                        @error('attachment_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="gc-field">
                        <label for="attachment_file">{{ __('inspection_visits.attachments.file') }}</label>
                        <input id="attachment_file" type="file" name="attachment_file" class="form-control @error('attachment_file') is-invalid @enderror" required>
                        @error('attachment_file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-outline-primary btn-sm mt-2">{{ __('inspection_visits.attachments.upload') }}</button>
            </form>

            @if ($visit->attachments->isEmpty())
                <div class="gc-empty">{{ __('inspection_visits.attachments.empty') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('inspection_visits.attachments.name') }}</th>
                                <th>{{ __('inspection_visits.attachments.uploaded_at') }}</th>
                                <th>{{ __('inspection_visits.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visit->attachments as $attachment)
                                <tr>
                                    <td>{{ $attachment->displayLabel() }}</td>
                                    <td>{{ optional($attachment->created_at)->format('Y-m-d H:i') ?: '—' }}</td>
                                    <td>
                                        @if (! empty($attachmentUrls[$attachment->id]))
                                            <a href="{{ $attachmentUrls[$attachment->id] }}" target="_blank" rel="noopener" class="gc-link-count">
                                                {{ __('inspection_visits.attachments.open') }}
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
            <h2 class="iv-section-title">{{ __('inspection_visits.notices.title') }}</h2>
            @if ($visit->replySubmissions->isEmpty())
                <div class="gc-empty">{{ __('inspection_visits.notices.empty') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('inspection_visits.attachments.uploaded_at') }}</th>
                                <th>{{ __('inspection_visits.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visit->replySubmissions as $submission)
                                <tr>
                                    <td>{{ optional($submission->created_at)->format('Y-m-d H:i') ?: '—' }}</td>
                                    <td>
                                        @if (! empty($noticeUrls[$submission->id]))
                                            <a href="{{ $noticeUrls[$submission->id] }}" target="_blank" rel="noopener" class="gc-link-count">
                                                {{ __('inspection_visits.attachments.open') }}
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
            <h2 class="iv-section-title">{{ __('inspection_visits.replies.title') }}</h2>
            @if ($visit->replies->isEmpty())
                <div class="gc-empty">{{ __('inspection_visits.replies.empty') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('inspection_visits.replies.reply') }}</th>
                                <th>{{ __('inspection_visits.replies.date') }}</th>
                                <th>{{ __('inspection_visits.replies.source') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visit->replies->sortByDesc('id') as $reply)
                                <tr>
                                    <td>{{ $reply->reply }}</td>
                                    <td>{{ optional($reply->created_at)->format('Y-m-d H:i') ?: '—' }}</td>
                                    <td>{{ (int) $reply->created_by_type === 2 ? __('inspection_visits.replies.management') : __('inspection_visits.replies.department') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="gc-panel mt-3">
            <h2 class="iv-section-title">{{ __('inspection_visits.returns.title') }}</h2>
            @if ($visit->returnedItems->isEmpty())
                <div class="gc-empty">{{ __('inspection_visits.returns.empty') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('inspection_visits.returns.finding') }}</th>
                                <th>{{ __('inspection_visits.returns.reason') }}</th>
                                <th>{{ __('inspection_visits.attachments.uploaded_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visit->returnedItems as $item)
                                <tr>
                                    <td>{{ $item->finding?->abuse_note_title ?: '—' }}</td>
                                    <td>{{ $item->reason }}</td>
                                    <td>
                                        {{ optional($item->created_at)->format('Y-m-d H:i') ?: '—' }}
                                        @if ($item->uploaded_file)
                                            <a class="gc-link-count ms-2" href="{{ route('modules.inspection-visits.returned.download', [$visit->id, $item->id]) }}">
                                                {{ __('inspection_visits.attachments.open') }}
                                            </a>
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
            <h2 class="iv-section-title">{{ __('inspection_visits.timeline.title') }}</h2>
            @if ($visit->timelineEntries->isEmpty())
                <div class="gc-empty">{{ __('inspection_visits.timeline.empty') }}</div>
            @else
                <ul class="iv-timeline">
                    @foreach ($visit->timelineEntries->sortByDesc('id') as $entry)
                        <li>
                            <span class="gc-badge" style="background-color: {{ $entry->statusRelation?->badgeColor() ?: '#64748b' }};">
                                {{ $entry->statusRelation?->localizedName() ?: __('inspection_visits.status_unknown') }}
                            </span>
                            <span class="iv-timeline__date">
                                @if (is_numeric($entry->date))
                                    {{ date('Y-m-d H:i', (int) $entry->date) }}
                                @else
                                    {{ $entry->date }}
                                @endif
                            </span>
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
            var noticeWrap = document.getElementById('noticeFileWrap');
            var returnWrap = document.getElementById('returnReasonsWrap');
            if (!statusSelect || !noticeWrap || !returnWrap) return;

            var STATUS_ENTITY_NOTIFIED = {{ $statusEntityNotified }};
            var STATUS_RETURNED = {{ $statusReturned }};

            function sync() {
                var value = parseInt(statusSelect.value || '0', 10);
                noticeWrap.hidden = value !== STATUS_ENTITY_NOTIFIED;
                returnWrap.hidden = value !== STATUS_RETURNED;
            }

            statusSelect.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush
