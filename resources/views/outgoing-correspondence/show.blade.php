@extends('layouts.app')

@section('title', __('outgoing_correspondence.detail'))
@section('sidebar_heading', __('outgoing_correspondence.title'))
@section('sidebar_subheading', __('outgoing_correspondence.detail_subtitle'))

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
        $statusEntityReplied = 11;
        $statusSupplementary = 13;
        $defaultSupplementary = old('supplementary_content', $item->defaultSupplementaryContent());
    @endphp
    <div class="hm-gc hm-iv">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.outgoing-correspondence.index') }}">{{ __('outgoing_correspondence.list') }}</a>
            <span>/</span>
            <span class="is-chip">{{ $item->displayNumber() }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('outgoing_correspondence.detail') }}</h1>
                <p>{{ $item->subject() ?: $item->displayNumber() }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="gc-badge" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                <a href="{{ route('modules.outgoing-correspondence.print', $item->id) }}" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener">
                    {{ __('outgoing_correspondence.print.button') }}
                </a>
                @if ((int) $item->status === 5)
                    <a href="{{ $departmentReviseUrl }}" class="btn btn-outline-success btn-sm" target="_blank" rel="noopener">
                        {{ __('outgoing_correspondence.department_revise.open_link') }}
                    </a>
                @endif
                <a href="{{ route('modules.outgoing-correspondence.index') }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('outgoing_correspondence.back_to_list') }}
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="gc-panel">
            <div class="gc-detail-grid">
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.table.number') }}</span>
                    <span class="gc-detail-item__value">{{ $item->displayNumber() }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.registration_number') }}</span>
                    <span class="gc-detail-item__value">{{ $item->registration_number ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.year') }}</span>
                    <span class="gc-detail-item__value">{{ $item->year ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.sector') }}</span>
                    <span class="gc-detail-item__value">{{ $item->sector?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.authority') }}</span>
                    <span class="gc-detail-item__value">{{ $item->authority?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.sender_title') }}</span>
                    <span class="gc-detail-item__value">{{ $item->senderTitle?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.recipient_name') }}</span>
                    <span class="gc-detail-item__value">{{ $item->sender ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.sender_gender') }}</span>
                    <span class="gc-detail-item__value">{{ $item->senderGenderLabel() }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.job_title') }}</span>
                    <span class="gc-detail-item__value">{{ $item->job_title ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.receiving_mechanism') }}</span>
                    <span class="gc-detail-item__value">{{ $item->receivingMechanism?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.issue_date') }}</span>
                    <span class="gc-detail-item__value">{{ $item->issue_date?->format('Y-m-d H:i') ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.response_deadline') }}</span>
                    <span class="gc-detail-item__value">{{ $item->receiving_response_date?->format('Y-m-d H:i') ?: '—' }}</span>
                </div>
                <div class="gc-detail-item">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.branch') }}</span>
                    <span class="gc-detail-item__value">{{ $item->branch?->localizedName() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item" style="grid-column: 1 / -1;">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.subject') }}</span>
                    <span class="gc-detail-item__value">{{ $item->subject() ?: '—' }}</span>
                </div>
                <div class="gc-detail-item" style="grid-column: 1 / -1;">
                    <span class="gc-detail-item__label">{{ __('outgoing_correspondence.fields.letter_content') }}</span>
                    <span class="gc-detail-item__value" style="white-space: pre-wrap;">{{ $item->letter_content ?: '—' }}</span>
                </div>
            </div>
        </section>

        <section class="gc-panel mt-3" id="status-update">
            <h2 class="iv-section-title">{{ __('outgoing_correspondence.status_form.title') }}</h2>
            <p class="text-muted mb-3">{{ __('outgoing_correspondence.status_form.subtitle') }}</p>

            @if ($updatableStatuses->isEmpty())
                <div class="gc-empty">{{ __('outgoing_correspondence.status_form.empty_statuses') }}</div>
            @else
                <form method="POST" action="{{ route('modules.outgoing-correspondence.status', $item->id) }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="gc-form-grid">
                        <div class="gc-field">
                            <label for="status_id">{{ __('outgoing_correspondence.status_form.status') }}</label>
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
                                <label class="form-check-label" for="confirm_approval">{{ __('outgoing_correspondence.status_form.confirm_approval') }}</label>
                            </div>
                            @error('confirm_approval') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="shipmentNumberWrap" hidden>
                            <label for="shipment_number">{{ __('outgoing_correspondence.status_form.shipment_number') }}</label>
                            <input id="shipment_number" type="text" name="shipment_number" value="{{ old('shipment_number') }}" class="form-control @error('shipment_number') is-invalid @enderror" maxlength="26">
                            @error('shipment_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="receiptDateWrap" hidden>
                            <label for="date_time_receipt">{{ __('outgoing_correspondence.status_form.date_time_receipt') }}</label>
                            <input id="date_time_receipt" type="datetime-local" name="date_time_receipt" value="{{ old('date_time_receipt') }}" class="form-control @error('date_time_receipt') is-invalid @enderror">
                            @error('date_time_receipt') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="postalEmployeeWrap" hidden>
                            <label for="postal_employee_name">{{ __('outgoing_correspondence.status_form.postal_employee_name') }}</label>
                            <input id="postal_employee_name" type="text" name="postal_employee_name" value="{{ old('postal_employee_name') }}" class="form-control @error('postal_employee_name') is-invalid @enderror" maxlength="100">
                            @error('postal_employee_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="returnDateWrap" hidden>
                            <label for="return_date">{{ __('outgoing_correspondence.status_form.return_date') }}</label>
                            <input id="return_date" type="datetime-local" name="return_date" value="{{ old('return_date') }}" class="form-control @error('return_date') is-invalid @enderror">
                            @error('return_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="registrationNumberWrap" hidden>
                            <label for="registration_number">{{ __('outgoing_correspondence.status_form.registration_number') }}</label>
                            <input id="registration_number" type="text" name="registration_number" value="{{ old('registration_number') }}" class="form-control @error('registration_number') is-invalid @enderror" maxlength="20">
                            @error('registration_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="deliveredByWrap" hidden>
                            <label for="delivered_by">{{ __('outgoing_correspondence.status_form.delivered_by') }}</label>
                            <input id="delivered_by" type="text" name="delivered_by" value="{{ old('delivered_by') }}" class="form-control @error('delivered_by') is-invalid @enderror" maxlength="150">
                            @error('delivered_by') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="deliveryDateWrap" hidden>
                            <label for="delivery_date">{{ __('outgoing_correspondence.status_form.delivery_date') }}</label>
                            <input id="delivery_date" type="datetime-local" name="delivery_date" value="{{ old('delivery_date') }}" class="form-control @error('delivery_date') is-invalid @enderror">
                            @error('delivery_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field gc-span-2" id="reasonWrap" hidden>
                            <label for="reason">{{ __('outgoing_correspondence.status_form.reason') }}</label>
                            <textarea id="reason" name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
                            @error('reason') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field gc-span-2" id="supplementaryWrap" hidden>
                            <label for="supplementary_content">{{ __('outgoing_correspondence.status_form.supplementary_content') }}</label>
                            <textarea id="supplementary_content" name="supplementary_content" rows="8" class="form-control @error('supplementary_content') is-invalid @enderror">{{ $defaultSupplementary }}</textarea>
                            @error('supplementary_content') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="gc-field" id="statusFileWrap" hidden>
                            <label for="status_file" id="statusFileLabel">{{ __('outgoing_correspondence.status_form.status_file') }}</label>
                            <input id="status_file" type="file" name="status_file" class="form-control @error('status_file') is-invalid @enderror">
                            @error('status_file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">{{ __('outgoing_correspondence.status_form.submit') }}</button>
                    </div>
                </form>
            @endif
        </section>

        <section class="gc-panel mt-3">
            <h2 class="iv-section-title">{{ __('outgoing_correspondence.fields.attachments') }}</h2>
            @if ($item->attachments->isEmpty())
                <div class="gc-empty">{{ __('outgoing_correspondence.no_attachment') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('outgoing_correspondence.fields.attachment_name') }}</th>
                                <th>{{ __('outgoing_correspondence.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($item->attachments as $file)
                                <tr>
                                    <td>{{ $file->displayLabel() }}</td>
                                    <td>
                                        @if (! empty($attachmentUrls[$file->id]))
                                            <a href="{{ $attachmentUrls[$file->id] }}" target="_blank" rel="noopener" class="gc-link-count">
                                                {{ __('outgoing_correspondence.open_attachment') }}
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
            <h2 class="iv-section-title">{{ __('outgoing_correspondence.supplementary.title') }}</h2>
            @if ($item->supplementaryLetters->isEmpty())
                <div class="gc-empty">{{ __('outgoing_correspondence.supplementary.empty') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('outgoing_correspondence.supplementary.serial') }}</th>
                                <th>{{ __('outgoing_correspondence.supplementary.created_at') }}</th>
                                <th>{{ __('outgoing_correspondence.supplementary.content') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($item->supplementaryLetters->sortByDesc('id') as $supplementary)
                                <tr>
                                    <td class="gc-col-date">{{ $supplementary->serial_no }}</td>
                                    <td class="gc-col-date">
                                        {{ $supplementary->created_at?->format('Y-m-d H:i') ?: '—' }}
                                    </td>
                                    <td>
                                        <span style="white-space: pre-wrap;">{{ $supplementary->details ?: '—' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="gc-panel mt-3">
            <h2 class="iv-section-title">{{ __('outgoing_correspondence.timeline.title') }}</h2>
            @if ($item->timelineEntries->isEmpty())
                <div class="gc-empty">{{ __('outgoing_correspondence.timeline.empty') }}</div>
            @else
                <div class="gc-table-wrap">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>{{ __('outgoing_correspondence.fields.status') }}</th>
                                <th>{{ __('outgoing_correspondence.timeline.notice') }}</th>
                                <th>{{ __('outgoing_correspondence.table.date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($item->timelineEntries->sortByDesc('id') as $entry)
                                <tr>
                                    <td>
                                        <span class="gc-badge" style="background-color: {{ $entry->status?->badgeColor() ?: '#64748b' }};">
                                            {{ $entry->status?->localizedName() ?: __('outgoing_correspondence.status_unknown') }}
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
            var STATUS_ENTITY_REPLIED = {{ $statusEntityReplied }};
            var STATUS_SUPPLEMENTARY = {{ $statusSupplementary }};

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
                supplementary: document.getElementById('supplementaryWrap'),
                statusFile: document.getElementById('statusFileWrap')
            };
            var statusFileLabel = document.getElementById('statusFileLabel');
            var replyFileLabel = @json(__('outgoing_correspondence.status_form.reply_file'));
            var supportFileLabel = @json(__('outgoing_correspondence.status_form.status_file'));

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
                setVisible(wraps.supplementary, statusId === STATUS_SUPPLEMENTARY);
                setVisible(
                    wraps.statusFile,
                    statusId === STATUS_SHIPMENT
                        || statusId === STATUS_POSTAL
                        || statusId === STATUS_ENTITY
                        || statusId === STATUS_RETURNED_ENTITY
                        || statusId === STATUS_ENTITY_REPLIED
                );

                if (statusFileLabel) {
                    statusFileLabel.textContent = statusId === STATUS_ENTITY_REPLIED
                        ? replyFileLabel
                        : supportFileLabel;
                }
            }

            statusSelect.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush
