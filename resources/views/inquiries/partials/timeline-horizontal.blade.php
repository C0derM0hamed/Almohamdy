@php
    $timelineCount = count($timeline);
    $gradientStops = [];

    if ($timelineCount > 0) {
        foreach ($timeline as $index => $event) {
            $percent = $timelineCount === 1 ? 0 : round(($index / ($timelineCount - 1)) * 100, 1);
            $color = $event['status_color'] ?? '#2456e8';
            $gradientStops[] = $color.' '.$percent.'%';
        }
    }

    $timelineGradient = $timelineCount > 0
        ? 'linear-gradient(90deg, '.implode(', ', $gradientStops).')'
        : 'linear-gradient(90deg, #2456e8, #0d43b8)';
@endphp

<article class="inq-timeline-card" id="inquiry-timeline">
    <div class="inq-timeline-card__head">
        <span class="inq-timeline-card__icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
        <div>
            <h2>{{ __('inquiries.timeline') }}</h2>
            <p>{{ __('inquiries.timeline_subtitle') }}</p>
        </div>
    </div>

    @if ($timelineCount > 0)
        <div class="inq-timeline-wrap">
            <div
                class="inq-timeline {{ $timelineCount === 1 ? 'inq-timeline--single' : '' }}"
                style="grid-template-columns: repeat({{ $timelineCount }}, minmax(160px, 1fr)); --inq-timeline-gradient: {{ $timelineGradient }};"
            >
                @foreach ($timeline as $event)
                    @php
                        $statusColor = $event['status_color'] ?? '#2456e8';
                    @endphp
                    <div class="inq-timeline-step">
                        <div
                            class="inq-timeline-step__dot"
                            style="--inq-step-color: {{ $statusColor }};"
                            aria-hidden="true"
                        >
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <div class="inq-timeline-step__title">{{ $event['message'] }}</div>
                        <div class="inq-timeline-step__date">
                            <span>{{ $event['date'] }}</span>
                            <span>{{ $event['time'] }}</span>
                        </div>
                        <div class="inq-timeline-step__meta">
                            <span class="inq-timeline-step__actor">{{ $event['actor_name'] ?: '—' }}</span>
                            <span class="inq-timeline-step__department">{{ $event['department'] ?: '—' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="hm-empty-state hm-empty-state--in-card">
            <i class="bi bi-clock-history" aria-hidden="true"></i>
            <p class="mb-0">{{ __('inquiries.no_timeline') }}</p>
        </div>
    @endif
</article>
