@php
    use App\Support\Complaints\ComplaintTimelinePresentation;

    $timelineCount = count($timeline);
@endphp

<article class="cp-info-card cp-timeline-card" id="complaint-timeline">
    <div class="cp-info-card__head">
        <span class="cp-info-card__icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
        <h2>{{ __('complaints.timeline') }}</h2>
    </div>

    @if ($timelineCount > 0)
        <div class="cp-timeline-wrap">
            <div
                class="cp-timeline"
                style="grid-template-columns: repeat({{ $timelineCount }}, minmax(140px, 1fr)); --cp-timeline-gradient: {{ ComplaintTimelinePresentation::connectorGradient($timeline) }};"
            >
                @foreach ($timeline as $index => $event)
                    @php
                        $reply = $event['reply'];
                        $statusId = (int) $reply->complaint_status_id;
                        $isLast = $index === $timelineCount - 1;
                        $tone = ComplaintTimelinePresentation::toneFor($statusId);

                        if ($isLast && $statusId === 4) {
                            $tone = 'danger';
                        }

                        $icon = ($isLast && $statusId === 4)
                            ? 'bi-arrow-up-right'
                            : ComplaintTimelinePresentation::horizontalIconFor($statusId);
                        $description = trim((string) $reply->details) !== ''
                            ? $reply->details
                            : $event['status_label'];
                    @endphp
                    <div class="cp-timeline-step cp-timeline-step--{{ $tone }}">
                        <div class="cp-timeline-step__dot" aria-hidden="true">
                            <i class="bi {{ $icon }}"></i>
                        </div>
                        <div class="cp-timeline-step__title">{{ $event['status_label'] }}</div>
                        <div class="cp-timeline-step__date">{{ $reply->formattedCreatedAt() }}</div>
                        <p class="cp-timeline-step__desc">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <p class="cp-timeline-empty">{{ __('complaints.no_timeline') }}</p>
    @endif
</article>
