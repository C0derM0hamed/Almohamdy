@php
    $isBar = ($variant ?? 'bar') === 'bar';
@endphp

<nav aria-label="{{ __('breadcrumbs.aria_label') }}" @class(['dd-breadcrumb', 'dd-breadcrumb--bar' => $isBar, 'dd-breadcrumb--plain' => ! $isBar])>
    @if (! $isBar && isset($items[0]['url']))
        <a href="{{ $items[0]['url'] }}" class="dd-breadcrumb-home" aria-label="{{ $items[0]['label'] }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
        </a>
    @endif

    @foreach ($items as $index => $item)
        @if ($index > 0)
            @if ($isBar)
                <span class="dd-breadcrumb-sep" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            @else
                <span class="dd-breadcrumb-sep-char" aria-hidden="true">›</span>
            @endif
        @endif

        @if (! empty($item['chip']))
            <span class="dd-chip">{{ $item['label'] }}</span>
        @elseif (! empty($item['url']))
            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
        @elseif (! empty($item['active']))
            <strong>{{ $item['label'] }}</strong>
        @else
            <span>{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
