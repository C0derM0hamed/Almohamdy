@php
    $size = $size ?? 24;
@endphp

<svg
    viewBox="0 0 24 24"
    width="{{ $size }}"
    height="{{ $size }}"
    fill="none"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>{!! $svg !!}</svg>
