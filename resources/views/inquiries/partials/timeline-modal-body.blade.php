@php
    $modalMode = $modalMode ?? false;
@endphp

<div class="inq-timeline-modal-body hm-inq" data-inquiry-id="{{ $inquiry->id }}">
    <div class="inq-summary-grid inq-summary-grid--modal" aria-label="{{ __('inquiries.timeline') }}">
        <article class="inq-summary-card">
            <small>{{ __('inquiries.form_fields.date') }}</small>
            <strong>#{{ $inquiry->id }}</strong>
        </article>
        <article class="inq-summary-card">
            <small>{{ __('inquiries.form_fields.enquirer') }}</small>
            <strong>{{ $inquiry->enquirerDisplayName() }}</strong>
        </article>
        <article class="inq-summary-card">
            <small>{{ __('inquiries.form_fields.status') }}</small>
            <strong>
                <span class="inq-status-pill" style="--inq-status-color: {{ $statusColor }};">
                    {{ $statusLabel }}
                </span>
            </strong>
        </article>
        <article class="inq-summary-card">
            <small>{{ __('inquiries.form_fields.department') }}</small>
            <strong>{{ $inquiry->inquiredSection?->legacyNavName() ?? '—' }}</strong>
        </article>
    </div>

    @include('inquiries.partials.timeline-horizontal', ['timeline' => $timeline])

    @if ($modalMode)
        <div class="inq-timeline-modal-actions">
            <a
                href="{{ route('modules.inquiries.pdf', ['direction' => $direction, 'inquiry' => $inquiry->id]) }}"
                class="btn hm-btn hm-btn--outline hm-inq-btn"
            >
                <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                {{ __('inquiries.download_pdf') }}
            </a>
        </div>
    @endif
</div>
