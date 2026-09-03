<article class="wan-panel wan-panel--breakdown wan-panel--{{ $variant ?? 'default' }}">
    <div class="wan-panel__head">
        <h2>{{ $title }}</h2>
    </div>

    @if (count($rows) === 0)
        @include('work-absence-notification.partials.wan-empty')
    @else
        <div class="wan-chart-wrap wan-chart-wrap--report">
            <canvas id="hmWanReportChart{{ $chartIndex }}" role="img" aria-label="{{ $title }}"></canvas>
        </div>
    @endif
</article>
