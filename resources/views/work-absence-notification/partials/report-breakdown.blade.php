@php
    $maxTotal = count($rows) > 0 ? max(array_column($rows, 'total')) : 0;
@endphp

<article class="wan-panel wan-panel--breakdown">
    <div class="wan-panel__head">
        <h2>{{ $title }}</h2>
    </div>

    @if (count($rows) === 0)
        @include('work-absence-notification.partials.wan-empty')
    @else
        <div class="wan-breakdown">
            <div class="wan-breakdown__head">
                <span>{{ $labelColumn }}</span>
                <span class="wan-breakdown__count-col">{{ __('work_absence_notification.reports.count') }}</span>
            </div>
            <ul class="wan-breakdown__list">
                @foreach ($rows as $row)
                    <li class="wan-breakdown__row">
                        <span class="wan-breakdown__label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                        <span class="wan-breakdown__track" aria-hidden="true">
                            <span
                                class="wan-breakdown__bar wan-breakdown__bar--{{ $variant ?? 'default' }}"
                                style="width: {{ $maxTotal > 0 ? round(($row['total'] / $maxTotal) * 100, 1) : 0 }}%"
                            ></span>
                        </span>
                        <span class="wan-breakdown__count">{{ number_format($row['total']) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</article>
