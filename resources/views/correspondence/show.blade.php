@extends('layouts.app')

@section('title', __('correspondence.detail'))
@section('sidebar_heading', __('correspondence.title'))
@section('sidebar_subheading', __('correspondence.detail_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inspection-visits.css') }}?v={{ filemtime(public_path('css/hm-inspection-visits.css')) }}" rel="stylesheet">
@endpush

@section('content')
    @php
        $selectedStatus = (int) old('status_id', 0);
        $statusApproved = 4;
        $statusReturnedDept = 5;
        $statusShipment = 6;
        $statusPostal = 7;
        $statusEntity = 8;
        $statusReturnedEntity = 9;
        $statusSpecialist = 10;
    @endphp
    <div class="hm-gc hm-iv">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.correspondence.index') }}">{{ __('correspondence.list') }}</a>
            <span>/</span>
            <span class="is-chip">{{ $item->displayNumber() }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('correspondence.detail') }}</h1>
                <p>{{ $item->subject() ?: $item->displayNumber() }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="gc-badge" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                <a href="{{ route('modules.correspondence.receipt', $item->id) }}" class="btn btn-outline-primary btn-sm">
                    {{ __('correspondence.actions.receipt') }}
                    <span class="badge text-bg-light ms-1">{{ $recipientsCount }}</span>
                </a>
                @if (in_array((int) $item->status, [1, 5], true))
                    <a href="{{ $departmentReplyUrl }}" class="btn btn-outline-success btn-sm" target="_blank" rel="noopener">
                        {{ __('correspondence.department_reply.open_link') }}
                    </a>
                @endif
                <a href="{{ route('modules.correspondence.index') }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('correspondence.back_to_list') }}
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="gc-panel">
            <div class="gc-detail-grid">
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.table.number') }}</span>
                    <span class="gc-detail-item__value">{{ $item->displayNumber() }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.sector') }}</span>
                    <span class="gc-detail-item__value">{{ $item->sector?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.authority') }}</span>
                    <span class="gc-detail-item__value">{{ $item->authority?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.sender_title') }}</span>
                    <span class="gc-detail-item__value">{{ $item->senderTitle?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.sender') }}</span>
                    <span class="gc-detail-item__value">{{ $item->sender ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.sender_gender') }}</span>
                    <span class="gc-detail-item__value">{{ $item->senderGenderLabel() }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.job_title') }}</span>
                    <span class="gc-detail-item__value">{{ $item->job_title ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.receiving_mechanism') }}</span>
                    <span class="gc-detail-item__value">{{ $item->receivingMechanism?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.issue_date') }}</span>
                    <span class="gc-detail-item__value">{{ $item->issue_date?->format('Y-m-d H:i') ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.received_date') }}</span>
                    <span class="gc-detail-item__value">{{ $item->received_date?->format('Y-m-d H:i') ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.branch') }}</span>
                    <span class="gc-detail-item__value">{{ $item->branch?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.section') }}</span>
                    <span class="gc-detail-item__value">{{ $item->section?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.response_deadline') }}</span>
                    <span class="gc-detail-item__value">{{ $item->receiving_response_date?->format('Y-m-d H:i') ?: '—' }}</span>
                </div>
                <div class="gc-detail-item" style="grid-column: 1 / -1;">
                    <span class="gc-detail-item__label">{{ __('correspondence.fields.subject') }}</span>
                    <span class="gc-detail-item__value">{{ $item->subject() ?: '—' }}</span>
                </div>
            </div>
        </section>

        <section class="gc-panel mt-3" id="status-update">
            <h2 class="iv-section-title">{{ __('correspondence.status_form.title') }}</h2>
            <p class="text-muted mb-3">{{ __('correspondence.status_form.subtitle') }}</p>

            @if ($updatableStatuses->isEmpty())
                <div class="gc-empty">{{ __('correspondence.status_form.empty_statuses') }}</div>
            @else
                <form method="POST" action="{{ route('modules.correspondence.status', $item->id) }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="gc-form-grid">
                        <div class="gc-field">
                            <label for="status_id">{{ __('correspondence.status_form.status') }}</label>
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

                        <div class="gc-field gc-span-2" id="approvalWrap" hidden>
                            <div class="form-check">
                                <input class="form-check-input @error('confirm_approval') is-invalid @enderror" type="checkbox" name="confirm_approval" id="confirm_approval" value="1" @checked(old('confirm_approval'))>
                                <label class="form-check-label" for="confirm_approval">{{ __('correspondence.status_form.confirm_approval') }}</label>
                            </div>
                            @error('confirm_approval') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="shipmentNumberWrap" hidden>
                            <label for="shipment_number">{{ __('correspondence.status_form.shipment_number') }}</label>
                            <input id="shipment_number" type="text" name="shipment_number" value="{{ old('shipment_number') }}" class="form-control @error('shipment_number') is-invalid @enderror" maxlength="26">
                            @error('shipment_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="receiptDateWrap" hidden>
                            <label for="date_time_receipt">{{ __('correspondence.status_form.date_time_receipt') }}</label>
                            <input id="date_time_receipt" type="datetime-local" name="date_time_receipt" value="{{ old('date_time_receipt') }}" class="form-control @error('date_time_receipt') is-invalid @enderror">
                            @error('date_time_receipt') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="postalEmployeeWrap" hidden>
                            <label for="postal_employee_name">{{ __('correspondence.status_form.postal_employee_name') }}</label>
                            <input id="postal_employee_name" type="text" name="postal_employee_name" value="{{ old('postal_employee_name') }}" class="form-control @error('postal_employee_name') is-invalid @enderror" maxlength="100">
                            @error('postal_employee_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="returnDateWrap" hidden>
                            <label for="return_date">{{ __('correspondence.status_form.return_date') }}</label>
                            <input id="return_date" type="datetime-local" name="return_date" value="{{ old('return_date') }}" class="form-control @error('return_date') is-invalid @enderror">
                            @error('return_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="registrationNumberWrap" hidden>
                            <label for="registration_number">{{ __('correspondence.status_form.registration_number') }}</label>
                            <input id="registration_number" type="text" name="registration_number" value="{{ old('registration_number') }}" class="form-control @error('registration_number') is-invalid @enderror" maxlength="20">
                            @error('registration_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="deliveredByWrap" hidden>
                            <label for="delivered_by">{{ __('correspondence.status_form.delivered_by') }}</label>
                            <input id="delivered_by" type="text" name="delivered_by" value="{{ old('delivered_by') }}" class="form-control @error('delivered_by') is-invalid @enderror" maxlength="150">
                            @error('delivered_by') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="deliveryDateWrap" hidden>
                            <label for="delivery_date">{{ __('correspondence.status_form.delivery_date') }}</label>
                            <input id="delivery_date" type="datetime-local" name="delivery_date" value="{{ old('delivery_date') }}" class="form-control @error('delivery_date') is-invalid @enderror">
                            @error('delivery_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field gc-span-2" id="reasonWrap" hidden>
                            <label for="reason">{{ __('correspondence.status_form.reason') }}</label>
                            <textarea id="reason" name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
                            @error('reason') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="statusFileWrap" hidden>
                            <label for="status_file">{{ __('correspondence.status_form.status_file') }}</label>
                            <input id="status_file" type="file" name="status_file" class="form-control @error('status_file') is-invalid @enderror">
                            @error('status_file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">{{ __('correspondence.status_form.submit') }}</button>
                    </div>
                </form>
            @endif
        </section>

        <section class="gc-panel mt-3">
            <h2 class="iv-section-title">{{ __('correspondence.fields.attachments') }}</h2>
            @if ($item->attachments->isEmpty())
                <div class="gc-empty">{{ __('correspondence.no_attachment') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('correspondence.fields.attachments') }}</th>
                                <th>{{ __('correspondence.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($item->attachments as $file)
                                <tr>
                                    <td>{{ $file->displayLabel() }}</td>
                                    <td>
                                        @if (! empty($attachmentUrls[$file->id]))
                                            <a href="{{ $attachmentUrls[$file->id] }}" target="_blank" rel="noopener" class="gc-link-count">
                                                {{ __('correspondence.open_attachment') }}
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
            <h2 class="iv-section-title">{{ __('correspondence.recipients.title') }}</h2>
            @if ($item->receiptReports->isEmpty())
                <div class="gc-empty">{{ __('correspondence.recipients.empty') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('correspondence.recipients.name') }}</th>
                                <th>{{ __('correspondence.recipients.department') }}</th>
                                <th>{{ __('correspondence.recipients.viewing_status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($item->receiptReports as $report)
                                <tr>
                                    <td>{{ $report->administrator?->displayName() ?: '—' }}</td>
                                    <td>{{ $report->administrator?->section?->localizedName() ?: '—' }}</td>
                                    <td>
                                        @if ($report->hasBeenViewed())
                                            <span class="gc-badge" style="background-color: #15803d;">
                                                {{ __('correspondence.recipients.viewed') }}
                                            </span>
                                        @else
                                            <span class="gc-badge" style="background-color: #64748b;">
                                                {{ __('correspondence.recipients.not_viewed') }}
                                            </span>
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
            <h2 class="iv-section-title">{{ __('correspondence.timeline.title') }}</h2>
            @if ($item->timelineEntries->isEmpty())
                <div class="gc-empty">{{ __('correspondence.timeline.empty') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('correspondence.fields.status') }}</th>
                                <th>{{ __('correspondence.timeline.notice') }}</th>
                                <th>{{ __('correspondence.table.date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($item->timelineEntries->sortByDesc('id') as $entry)
                                <tr>
                                    <td>
                                        <span class="gc-badge" style="background-color: {{ $entry->status?->badgeColor() ?: '#64748b' }};">
                                            {{ $entry->status?->localizedName() ?: __('correspondence.status_unknown') }}
                                        </span>
                                    </td>
                                    <td>{{ $entry->notice ?: '—' }}</td>
                                    <td class="gc-col-date">
                                        @if (is_numeric($entry->date))
                                            {{ date('Y-m-d H:i', (int) $entry->date) }}
                                        @else
                                            {{ $entry->date ?: '—' }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var statusSelect = document.getElementById('status_id');
            if (!statusSelect) return;

            var STATUS_APPROVED = {{ $statusApproved }};
            var STATUS_RETURNED_DEPT = {{ $statusReturnedDept }};
            var STATUS_SHIPMENT = {{ $statusShipment }};
            var STATUS_POSTAL = {{ $statusPostal }};
            var STATUS_ENTITY = {{ $statusEntity }};
            var STATUS_RETURNED_ENTITY = {{ $statusReturnedEntity }};
            var STATUS_SPECIALIST = {{ $statusSpecialist }};

            var wraps = {
                approval: document.getElementById('approvalWrap'),
                shipmentNumber: document.getElementById('shipmentNumberWrap'),
                receiptDate: document.getElementById('receiptDateWrap'),
                postalEmployee: document.getElementById('postalEmployeeWrap'),
                returnDate: document.getElementById('returnDateWrap'),
                registrationNumber: document.getElementById('registrationNumberWrap'),
                deliveredBy: document.getElementById('deliveredByWrap'),
                deliveryDate: document.getElementById('deliveryDateWrap'),
                reason: document.getElementById('reasonWrap'),
                statusFile: document.getElementById('statusFileWrap')
            };

            function setVisible(el, visible) {
                if (!el) return;
                el.hidden = !visible;
            }

            function sync() {
                var statusId = parseInt(statusSelect.value || '0', 10);

                setVisible(wraps.approval, statusId === STATUS_APPROVED);
                setVisible(wraps.shipmentNumber, statusId === STATUS_SHIPMENT);
                setVisible(wraps.receiptDate, statusId === STATUS_POSTAL || statusId === STATUS_ENTITY);
                setVisible(wraps.postalEmployee, statusId === STATUS_POSTAL);
                setVisible(wraps.returnDate, statusId === STATUS_RETURNED_ENTITY);
                setVisible(wraps.registrationNumber, statusId === STATUS_SPECIALIST);
                setVisible(wraps.deliveredBy, statusId === STATUS_SPECIALIST);
                setVisible(wraps.deliveryDate, statusId === STATUS_SPECIALIST);
                setVisible(wraps.reason, statusId === STATUS_RETURNED_DEPT || statusId === STATUS_RETURNED_ENTITY);
                setVisible(
                    wraps.statusFile,
                    statusId === STATUS_SHIPMENT
                        || statusId === STATUS_POSTAL
                        || statusId === STATUS_ENTITY
                        || statusId === STATUS_RETURNED_ENTITY
                );
            }

            statusSelect.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush
