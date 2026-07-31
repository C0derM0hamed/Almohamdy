@php
    use App\Support\Complaints\ComplaintTimelinePresentation;

    $departmentName = $complaint->department?->localizedName() ?? '—';
@endphp

<div class="cp-timeline-modal" id="cpComplaintTimelineModal" hidden aria-hidden="true">
    <button type="button" class="cp-timeline-modal__backdrop" data-cp-timeline-close tabindex="-1" aria-hidden="true"></button>

    <div
        class="cp-timeline-modal__dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cpComplaintTimelineTitle"
    >
        <header class="cp-timeline-modal__head">
            <div>
                <h2 id="cpComplaintTimelineTitle">{{ __('complaints.timeline') }}</h2>
                <p>{{ __('complaints.timeline_modal_subtitle', ['number' => $displayNumber, 'department' => $departmentName]) }}</p>
            </div>
            <button type="button" class="cp-timeline-modal__close" data-cp-timeline-close aria-label="{{ __('complaints.close') }}">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>

        <div class="cp-timeline-modal__summary">
            <div class="cp-timeline-summary-card">
                <span class="cp-timeline-summary-card__label">{{ __('complaints.fields.complaint_no') }}</span>
                <strong class="cp-timeline-summary-card__value">{{ $displayNumber }}</strong>
            </div>
            <div class="cp-timeline-summary-card">
                <span class="cp-timeline-summary-card__label">{{ __('complaints.fields.department') }}</span>
                <strong class="cp-timeline-summary-card__value">{{ $departmentName }}</strong>
            </div>
            <div class="cp-timeline-summary-card">
                <span class="cp-timeline-summary-card__label">{{ __('complaints.fields.status') }}</span>
                <strong class="cp-timeline-summary-card__value">{{ $statusLabel }}</strong>
            </div>
            <div class="cp-timeline-summary-card">
                <span class="cp-timeline-summary-card__label">{{ __('complaints.timeline_last_update') }}</span>
                <strong class="cp-timeline-summary-card__value">{{ $complaint->formattedUpdatedAt() }}</strong>
            </div>
        </div>

        <div class="cp-timeline-modal__body">
            @if (count($timeline) > 0)
                <ol class="cp-timeline-vertical">
                    @foreach ($timeline as $event)
                        @php
                            $reply = $event['reply'];
                            $statusId = (int) $reply->complaint_status_id;
                            $icon = ComplaintTimelinePresentation::iconFor($statusId);
                            $description = trim((string) $reply->details) !== ''
                                ? $reply->details
                                : $event['status_label'];
                            $actor = $reply->creatorDisplayName();
                        @endphp
                        <li class="cp-timeline-event">
                            <span class="cp-timeline-event__dot" aria-hidden="true">
                                <i class="bi {{ $icon }}"></i>
                            </span>
                            <article class="cp-timeline-event__card">
                                <time class="cp-timeline-event__time" datetime="{{ $reply->formattedCreatedAt() }}">
                                    {{ $reply->formattedCreatedAt() }}
                                </time>
                                <h3 class="cp-timeline-event__title">{{ $event['status_label'] }}</h3>
                                <p class="cp-timeline-event__desc">{{ $description }}</p>
                                <footer class="cp-timeline-event__actor">
                                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                                    <span>{{ $actor }}</span>
                                </footer>
                            </article>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="cp-timeline-modal__empty">{{ __('complaints.no_timeline') }}</p>
            @endif
        </div>
    </div>
</div>
