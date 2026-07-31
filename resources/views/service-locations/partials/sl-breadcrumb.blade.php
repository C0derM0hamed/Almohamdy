<nav aria-label="{{ __('breadcrumbs.aria_label') }}" class="sl-breadcrumb sl-breadcrumb--bar">
    @foreach ($items as $index => $item)
        @if ($index > 0)
            <span class="sl-breadcrumb-sep" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </span>
        @endif

        @if (! empty($item['chip']))
            <span class="sl-chip">{{ $item['label'] }}</span>
        @elseif (! empty($item['url']))
            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
        @else
            <span>{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
