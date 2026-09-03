@extends('layouts.app')

@section('title', __('training.details'))
@section('sidebar_heading', __('training.'.$mode))
@section('figma_page_header', 'true')

@php
    $statusLabel = \App\Support\LocaleText::localizedValue($training->currentStatus?->name_ar ?? null, $training->currentStatus?->name_en ?? null) ?: (string) $training->status;
    $failedStatus = str_contains($statusLabel, 'غير مجتاز')
        || str_contains($statusLabel, 'مرفوض')
        || str_contains(strtolower($statusLabel), 'failed');
@endphp

@section('content')
<div class="hm-fm hm-training-detail" data-module="training-detail">
    @include('layouts.partials.figma-module-header', [
        'compact' => true,
        'crumbs' => [
            ['label' => __('training.'.$mode), 'url' => route($routes['index'])],
            ['label' => __('training.details')],
        ],
        'title' => __('training.details'),
        'subtitle' => '',
    ])

    @if(session('success'))
        <div class="alert alert-success hm-training-detail__notice" role="status">{{ session('success') }}</div>
    @endif

    <div class="hm-training-detail__heading">
        <div class="hm-training-detail__title-block">
            <h1>{{ __('training.details') }} #{{ $training->id }}</h1>
            <span class="hm-training-status {{ $failedStatus ? 'is-failed' : 'is-success' }}">
                <span aria-hidden="true"></span>{{ $statusLabel }}
            </span>
        </div>
        <a class="hm-training-back" href="{{ route($routes['index']) }}">
            {{ __('training.back') }}
            <img src="{{ asset('images/figma/training/back.svg') }}" alt="" width="18" height="18">
        </a>
    </div>

    <div class="hm-training-detail__grid">
        <section class="hm-training-status-card" aria-labelledby="trainingStatusTitle">
            <div class="hm-training-card-title">
                <span id="trainingStatusTitle">{{ __('training.update_status') }}</span>
                <span class="hm-training-card-icon" aria-hidden="true">
                    <img src="{{ asset('images/figma/training/status.svg') }}" alt="" width="18" height="18">
                </span>
            </div>

            <form method="POST" action="{{ route($routes['status'], $training->id) }}">
                @csrf
                <label for="managementStatus">{{ __('training.status') }}</label>
                <div class="hm-training-select">
                    <select name="status_id" id="managementStatus" required>
                        <option value="">—</option>
                        @foreach($managementStatuses as $status)
                            <option value="{{ $status->id }}" @disabled((int) $training->status === (int) $status->id)>{{ \App\Support\LocaleText::localizedValue($status->name_ar ?? null, $status->name_en ?? null) }}</option>
                        @endforeach
                    </select>
                    <img src="{{ asset('images/figma/training/select.svg') }}" alt="" width="18" height="18">
                </div>

                <div id="managementAck" class="hm-training-extra" hidden>
                    <input type="checkbox" name="acknowledgement" value="1" id="ack">
                    <label for="ack">{{ __('training.manager_ack') }}</label>
                </div>

                <div id="managementReason" class="hm-training-extra" hidden>
                    <label for="managementReasonText">{{ __('training.reason') }}</label>
                    <textarea id="managementReasonText" name="details" maxlength="200" rows="3"></textarea>
                </div>

                <button class="hm-training-save" type="submit">{{ __('training.save') }}</button>
            </form>
        </section>

        <section class="hm-training-info-card" aria-labelledby="trainingEmployeeTitle">
            <div class="hm-training-card-title hm-training-info-card__title">
                <span id="trainingEmployeeTitle">{{ __('training.employee_info') }}</span>
                <span class="hm-training-card-icon" aria-hidden="true">
                    <img src="{{ asset('images/figma/training/employee.svg') }}" alt="" width="18" height="18">
                </span>
            </div>

            <dl class="hm-training-facts">
                <div><dt>{{ __('training.employee') }}</dt><dd>{{ $training->employee?->displayName() ?: '—' }} @if($training->employee?->hr_username) <small>({{ $training->employee->hr_username }})</small> @endif</dd></div>
                <div><dt>{{ __('training.job_title') }}</dt><dd>{{ $training->employee?->jobTitle?->localizedName() ?? '—' }}</dd></div>
                <div><dt>{{ __('training.coordinator') }}</dt><dd>{{ $training->coordinator?->displayName() ?? '—' }}</dd></div>
                <div><dt>{{ __('training.branch') }}</dt><dd>{{ $training->branch?->localizedName() ?? '—' }}</dd></div>
                <div><dt>{{ __('training.begin_date') }}</dt><dd dir="ltr">{{ $training->begin_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt>{{ __('training.end_date') }}</dt><dd dir="ltr">{{ $training->endDate()?->format('Y-m-d') ?? '—' }}</dd></div>
            </dl>

            <div class="hm-training-schedule">
                <span class="hm-training-schedule__icon" aria-hidden="true">
                    <img src="{{ asset('images/figma/training/schedule.svg') }}" alt="" width="18" height="18">
                </span>
                <span>
                    <small>{{ __('training.schedule') }}</small>
                    <strong>{{ $training->days }} {{ __('training.day') }}، {{ $training->training_hour }} {{ __('training.hour_daily') }}، <bdi>{{ $training->time_from }} - {{ $training->time_to }}</bdi></strong>
                </span>
            </div>
        </section>
    </div>

    <section class="hm-training-documents" aria-labelledby="trainingDocumentsTitle">
        <div class="hm-training-documents__title">
            <h2 id="trainingDocumentsTitle">{{ __('training.documents') }}</h2>
            <span aria-hidden="true"><img src="{{ asset('images/figma/training/file.svg') }}" alt="" width="18" height="18"></span>
        </div>
        <div class="hm-training-documents__bar">
            <a class="is-primary" href="{{ route($routes['document'], [$training->id, 'plan']) }}">
                {{ __('training.plan_pdf') }}
                <img src="{{ asset('images/figma/training/download.svg') }}" alt="" width="24" height="24">
            </a>
            @if($training->hasSignedPdf())
                <a href="{{ route($routes['signed_pdf'], $training->id) }}">{{ __('training.signed_pdf') }}</a>
            @endif
            @foreach([3 => 'coordinator-passed', 4 => 'coordinator-failed', 6 => 'manager-passed', 7 => 'manager-failed'] as $status => $document)
                @if($timeline->contains('status_id', $status))
                    <a href="{{ route($routes['document'], [$training->id, $document]) }}">{{ __('training.document_'.$document) }}</a>
                @endif
            @endforeach
            <a href="{{ route($routes['timeline'], $training->id) }}">{{ __('training.timeline') }}</a>
        </div>
    </section>
</div>

@push('scripts')
<script>
(function () {
    const ackStatusId = @json($ackStatusId ?? null);
    const reasonStatusIds = @json($reasonStatusIds ?? []);
    const status = document.getElementById('managementStatus');
    const syncStatusFields = function () {
        if (!status) return;
        document.getElementById('managementAck').hidden = status.value !== String(ackStatusId);
        document.getElementById('managementReason').hidden = !reasonStatusIds.includes(Number(status.value));
    };
    status?.addEventListener('change', syncStatusFields);
    syncStatusFields();
})();
</script>
@endpush
@endsection
