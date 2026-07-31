@php
    $isBar = ($variant ?? 'bar') === 'bar';
@endphp

<nav aria-label="{{ __('breadcrumbs.aria_label') }}" @class(['wan-breadcrumb', 'wan-breadcrumb--bar' => $isBar])>
    @foreach ($items as $index => $item)
        @if ($index > 0)
            <span class="wan-breadcrumb__sep" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </span>
        @endif

        @if (! empty($item['chip']))
            <span class="wan-chip">{{ $item['label'] }}</span>
        @elseif (! empty($item['url']))
            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
        @elseif (! empty($item['current']))
            <span class="wan-breadcrumb__current" aria-current="page">{{ $item['label'] }}</span>
        @else
            <span>{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
