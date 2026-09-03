@php
    $tabs = $tabs ?? [];
@endphp

<nav class="fm-tabs" aria-label="{{ $ariaLabel ?? '' }}">
    @foreach ($tabs as $tab)
        <a href="{{ $tab['url'] ?? '#' }}" class="{{ ! empty($tab['active']) ? 'is-active' : '' }}">
            @if (! empty($tab['iconHtml']))
                {!! $tab['iconHtml'] !!}
            @endif
            {{ $tab['label'] ?? '' }}
        </a>
    @endforeach
</nav>
