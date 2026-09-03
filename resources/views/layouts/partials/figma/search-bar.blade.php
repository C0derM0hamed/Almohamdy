@php
    $title = $title ?? '';
    $subtitle = $subtitle ?? '';
    $action = $action ?? '';
    $method = $method ?? 'GET';
@endphp

<section class="fm-search">
    <div class="fm-search__head">
        <h2>{{ $title }}</h2>
        @if ($subtitle !== '')
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    <form class="fm-search__row" action="{{ $action }}" method="{{ $method }}" role="search">
        {{ $slot ?? '' }}
    </form>
</section>
