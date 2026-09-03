@extends('layouts.app')

@php
    $displayNumber = $complaint->displayNumber();
@endphp

@section('title', __('complaints.detail'))

@section('sidebar_heading', __('complaints.title'))
@section('sidebar_subheading', __('complaints.detail_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-complaints-redesign.css') }}?v={{ filemtime(public_path('css/hm-complaints-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-detail-stat-cards.css') }}?v={{ filemtime(public_path('css/hm-detail-stat-cards.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-cp hm-cp--detail">
        @include('complaints.partials.cp-breadcrumb', [
            'items' => [
                ['label' => __('complaints.dashboard'), 'url' => route('modules.complaints')],
                ['label' => __('complaints.list'), 'url' => route('modules.complaints')],
                ['label' => $displayNumber, 'chip' => true],
            ],
        ])

        <header class="cp-detail-head">
            <div class="cp-detail-head__copy">
                <h1>{{ __('complaints.detail') }}</h1>
                <div class="cp-detail-head__badges">
                    <span class="cp-detail-no">{{ $displayNumber }}</span>
                    <span class="cp-detail-status" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                </div>
            </div>

            <div class="cp-detail-head__actions">
                <a class="cp-btn cp-btn--outline" href="{{ route('modules.complaints.pdf', $complaint->id) }}">
                    <i class="bi bi-printer" aria-hidden="true"></i>
                    {{ __('complaints.print') }}
                </a>
            </div>
        </header>

        <section class="cp-detail-grid">
            <article class="cp-info-card">
                <div class="cp-info-card__head">
                    <span class="cp-info-card__icon" aria-hidden="true"><i class="bi bi-person"></i></span>
                    <h2>{{ __('complaints.sections.complainant_info') }}</h2>
                </div>
                <div class="cp-info-list">
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.complainant') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->localizedComplainantName() ?: '—' }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.patient') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->localizedPatientName() ?: '—' }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.mobile') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->mobile ?: '—' }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.file_no') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->file_number ?: '—' }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.department') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->department?->localizedName() ?? '—' }}</span>
                    </div>
                </div>
            </article>

            <article class="cp-info-card">
                <div class="cp-info-card__head">
                    <span class="cp-info-card__icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                    <h2>{{ __('complaints.sections.complaint_info') }}</h2>
                </div>
                <div class="cp-info-list">
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.complaint_no') }}</span>
                        <span class="cp-info-row__value">{{ $displayNumber }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.type') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->typeLabel() ?: '—' }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.date') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->formattedComplaintDate() }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.created_at') }}</span>
                        <span class="cp-info-row__value">
                            {{ $complaint->created_at ? \Carbon\Carbon::parse($complaint->created_at)->format('Y-m-d H:i') : '—' }}
                        </span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.defendant') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->defendant ?: '—' }}</span>
                    </div>
                    <div class="cp-info-row">
                        <span class="cp-info-row__label">{{ __('complaints.fields.details') }}</span>
                        <span class="cp-info-row__value">{{ $complaint->details ?: '—' }}</span>
                    </div>
                </div>
            </article>
        </section>

        <article class="cp-info-card cp-info-card--investigation">
            <div class="cp-info-card__head">
                <span class="cp-info-card__icon" aria-hidden="true"><i class="bi bi-search"></i></span>
                <h2>{{ __('complaints.sections.investigation') }}</h2>
            </div>
            <div class="cp-info-list">
                <div class="cp-info-row">
                    <span class="cp-info-row__label">{{ __('complaints.fields.result') }}</span>
                    <span class="cp-info-row__value">{{ $complaint->result ?: '—' }}</span>
                </div>
                <div class="cp-info-row">
                    <span class="cp-info-row__label">{{ __('complaints.fields.employee_investigation') }}</span>
                    <span class="cp-info-row__value">{{ $complaint->employee_investigation ?: '—' }}</span>
                </div>
                <div class="cp-info-row">
                    <span class="cp-info-row__label">{{ __('complaints.fields.status') }}</span>
                    <span class="cp-info-row__value">
                        <span class="cp-detail-status cp-detail-status--inline" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                    </span>
                </div>
            </div>
        </article>

        @include('complaints.partials.timeline-horizontal', ['timeline' => $timeline])

        @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if ($errors->any()) <div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div> @endif
        @if ((int) $complaint->status !== 5 && (int) $complaint->status !== 6)
            <section class="cp-info-card mt-4">
                <h2>{{ __('complaints.reply_title') }}</h2>
                <form method="POST" action="{{ route('modules.complaints.reply', $complaint->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3"><div class="col-md-6"><label class="form-label" for="status_id">{{ __('complaints.fields.status') }}</label><select class="form-select" id="status_id" name="status_id" required><option value="">—</option>@foreach($statusOptions as $status)<option value="{{ $status->id }}">{{ $status->localizedName() }}</option>@endforeach</select></div><div class="col-12"><label class="form-label" for="reply_details">{{ __('complaints.fields.reply') }}</label><textarea class="form-control" id="reply_details" name="details" rows="4" required></textarea></div><div class="col-12"><label class="form-label" for="reply_attachment">{{ __('complaints.fields.attachment') }}</label><input id="reply_attachment" type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf"></div></div>
                    <button class="cp-btn cp-btn--primary mt-3" type="submit">{{ __('complaints.save') }}</button>
                </form>
            </section>
        @endif

        <div class="cp-detail-actions">
            <a href="{{ route('modules.complaints') }}" class="cp-btn cp-btn--outline">
                <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-arrow-right' : 'bi-arrow-left' }}" aria-hidden="true"></i>
                {{ __('complaints.back_to_list') }}
            </a>
            <a href="{{ route('modules.complaints') }}" class="cp-btn cp-btn--primary">
                {{ __('complaints.view_list') }}
            </a>
        </div>
    </div>

    @include('complaints.partials.timeline-modal', [
        'complaint' => $complaint,
        'timeline' => $timeline,
        'displayNumber' => $displayNumber,
        'statusLabel' => $statusLabel,
    ])
@endsection

@push('scripts')
    <script src="{{ asset('js/hm-complaint-timeline-modal.js') }}?v={{ filemtime(public_path('js/hm-complaint-timeline-modal.js')) }}" defer></script>
@endpush
