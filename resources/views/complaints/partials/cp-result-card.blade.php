@php
    $statusLabel = (int) $complaint->status === 0
        ? __('complaints.status.new')
        : ($complaint->currentStatus?->localizedName() ?? '—');
    $statusColor = (int) $complaint->status === 0
        ? '#fce7f3'
        : ($complaint->currentStatus?->badgeColor() ?? '#e2e8f0');
    $priorityLabel = $complaint->priorityLabel();
    $priorityClass = (int) $complaint->priority === 1 ? 'is-high' : 'is-low';
@endphp

<article class="cp-result-card">
    <div class="cp-result-card__col cp-result-card__col--no">
        <div class="cp-result-card__meta-label">{{ __('complaints.columns.complaint_no') }}</div>
        <span class="cp-complaint-no" style="background-color: {{ $complaint->numberBadgeColor() }};">
            {{ $complaint->displayNumber() }}
        </span>
    </div>

    <div class="cp-result-card__col">
        <div class="cp-result-card__meta-label">{{ __('complaints.columns.complainant') }}</div>
        <div class="cp-result-card__value">{{ $complaint->localizedComplainantName() ?: '—' }}</div>
        @if ($complaint->file_number)
            <div class="cp-result-card__sub">{{ __('complaints.columns.file_no') }}: {{ $complaint->file_number }}</div>
        @endif
    </div>

    <div class="cp-result-card__col">
        <div class="cp-result-card__meta-label">{{ __('complaints.columns.department') }}</div>
        <div class="cp-result-card__value">{{ $complaint->department?->localizedName() ?? '—' }}</div>
    </div>

    <div class="cp-result-card__col">
        <div class="cp-result-card__meta-label">{{ __('complaints.columns.date') }}</div>
        <div class="cp-result-card__value">{{ $complaint->formattedComplaintDate() }}</div>
    </div>

    <div class="cp-result-card__col">
        <div class="cp-result-card__meta-label">{{ __('complaints.columns.status') }}</div>
        <span class="cp-status" style="background-color: {{ $statusColor }};">
            {{ $statusLabel }}
        </span>
    </div>

    <div class="cp-result-card__col">
        <div class="cp-result-card__meta-label">{{ __('complaints.columns.priority') }}</div>
        <span class="cp-priority {{ $priorityClass }}">{{ $priorityLabel }}</span>
    </div>

    <div class="cp-result-card__col cp-result-card__col--actions">
        <div class="cp-result-card__meta-label">{{ __('complaints.columns.actions') }}</div>
        <div class="cp-actions">
            <a href="{{ route('modules.complaints.show', $complaint->id) }}" class="cp-detail-btn">
                {{ __('complaints.view_detail') }}
            </a>
            <a
                href="{{ route('modules.complaints.show', ['complaint' => $complaint->id, 'timeline' => 1]) }}"
                class="cp-detail-btn cp-detail-btn--timeline"
            >
                {{ __('complaints.view_timeline') }}
            </a>
        </div>
    </div>
</article>
