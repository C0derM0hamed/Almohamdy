@php
    $modalMode = $modalMode ?? false;
@endphp

<div class="inq-timeline-modal-body hm-inq" data-inquiry-id="{{ $inquiry->id }}">
    <div
        class="inq-summary-grid inq-summary-grid--modal"
        aria-label="{{ __('inquiries.timeline') }}"
        dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    >
        @foreach ([
            ['label' => __('inquiries.inquiry_number'), 'value' => '#'.$inquiry->id, 'hint' => $inquiry->mobile, 'icon' => 'bi-hash', 'tone' => 'primary'],
            ['label' => __('inquiries.form_fields.enquirer'), 'value' => $inquiry->enquirerDisplayName(), 'hint' => __('inquiries.form_fields.department').': '.($inquiry->inquiredSection?->legacyNavName() ?? '—'), 'icon' => 'bi-person', 'tone' => 'dark'],
            ['label' => __('inquiries.form_fields.status'), 'value' => $statusLabel, 'hint' => __('inquiries.timeline_subtitle'), 'icon' => 'bi-activity', 'tone' => 'primary'],
        ] as $summary)
            <article class="inq-summary-card inq-summary-card--{{ $summary['tone'] }}">
                <span class="inq-summary-card__icon" aria-hidden="true"><i class="bi {{ $summary['icon'] }}"></i></span>
                <span class="inq-summary-card__copy">
                    <small>{{ $summary['label'] }}</small>
                    <strong>{{ $summary['value'] }}</strong>
                    <span>{{ $summary['hint'] }}</span>
                </span>
            </article>
        @endforeach
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
