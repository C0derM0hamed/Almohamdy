@php
    $timelineCount = count($timeline);
    $currentIndex = $timelineCount - 1;
    $currentStatusId = (int) ($inquiry->status ?? 0);
    $lastEventCompleted = $timelineCount > 0 && $currentStatusId === 6;
    $completedCount = $timelineCount === 0
        ? 0
        : max(0, $timelineCount - ($lastEventCompleted ? 0 : 1));
    $progress = $timelineCount > 0
        ? (int) round(($completedCount / $timelineCount) * 100)
        : 0;
@endphp

<article class="inq-timeline-card inq-timeline-card--vertical" id="inquiry-timeline">
    <div class="inq-timeline-card__head">
        <span class="inq-timeline-card__icon" aria-hidden="true">
            <i class="bi bi-clock-history"></i>
        </span>
        <div class="inq-timeline-card__heading">
            <h2>{{ __('inquiries.timeline') }}</h2>
            <p>{{ __('inquiries.timeline_subtitle') }}</p>
        </div>
        @if ($timelineCount > 0)
            <div class="inq-timeline-progress" aria-label="{{ __('inquiries.timeline_progress', ['completed' => $completedCount, 'total' => $timelineCount]) }}">
                <div class="inq-timeline-progress__copy">
                    <span>{{ __('inquiries.timeline_progress', ['completed' => $completedCount, 'total' => $timelineCount]) }}</span>
                    <strong>{{ $progress }}%</strong>
                </div>
                <div class="inq-timeline-progress__bar" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                    <span style="width: {{ $progress }}%"></span>
                </div>
            </div>
        @endif
    </div>

    @if ($timelineCount > 0)
        <div class="inq-timeline-wrap inq-timeline-wrap--vertical">
            <div class="inq-workflow">
                <div class="inq-workflow__line" aria-hidden="true">
                    <span style="height: {{ $progress }}%"></span>
                </div>

                @foreach ($timeline as $index => $event)
                    @php
                        $eventStatusId = (int) ($event['status_id'] ?? 0);
                        $isCurrent = $index === $currentIndex && ! $lastEventCompleted;
                        $isCompleted = $index < $currentIndex || $lastEventCompleted;
                        $stateClass = $isCurrent ? 'is-current' : ($isCompleted ? 'is-completed' : 'is-pending');
                        $eventIcon = match ($eventStatusId) {
                            3 => 'bi-search',
                            4 => 'bi-telephone',
                            5 => 'bi-bell-slash',
                            6 => 'bi-flag',
                            999999 => 'bi-arrow-left-right',
                            default => 'bi-inbox',
                        };
                        $stateLabel = $isCurrent
                            ? __('inquiries.timeline_current')
                            : ($isCompleted ? __('inquiries.timeline_completed') : __('inquiries.timeline_pending'));
                    @endphp

                    <article class="inq-workflow__event {{ $stateClass }}">
                        <div class="inq-workflow__marker" aria-label="{{ $stateLabel }}">
                            @if ($isCompleted)
                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                            @else
                                <i class="bi {{ $eventIcon }}" aria-hidden="true"></i>
                            @endif
                        </div>

                        <div class="inq-workflow__content">
                            <div class="inq-workflow__topline">
                                <span class="inq-workflow__stage">
                                    <b>{{ $index + 1 }}</b>
                                    {{ __('inquiries.timeline_stage', ['number' => $index + 1]) }}
                                </span>
                                <span class="inq-workflow__state">
                                    @if ($isCurrent)
                                        <i class="bi bi-record-circle" aria-hidden="true"></i>
                                    @elseif ($isCompleted)
                                        <i class="bi bi-check2" aria-hidden="true"></i>
                                    @endif
                                    {{ $stateLabel }}
                                </span>
                            </div>

                            <div class="inq-workflow__main">
                                <span class="inq-workflow__event-icon" aria-hidden="true">
                                    <i class="bi {{ $eventIcon }}"></i>
                                </span>
                                <div class="inq-workflow__copy">
                                    <h3>{{ $event['message'] }}</h3>
                                    <div class="inq-workflow__date">
                                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                                        <span>{{ $event['date'] }}</span>
                                        <span class="inq-workflow__separator" aria-hidden="true"></span>
                                        <i class="bi bi-clock" aria-hidden="true"></i>
                                        <span>{{ $event['time'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="inq-workflow__meta">
                                <span class="inq-workflow__meta-item">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                    <strong>{{ $event['actor_name'] ?: '—' }}</strong>
                                </span>
                                <span class="inq-workflow__meta-item">
                                    <i class="bi bi-building" aria-hidden="true"></i>
                                    <span>{{ $event['department'] ?: '—' }}</span>
                                </span>
                            </div>
                        </div>
                    </article>
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
