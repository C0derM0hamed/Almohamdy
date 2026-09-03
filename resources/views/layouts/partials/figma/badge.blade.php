@php
    $tone = $tone ?? 'primary';
    $label = $label ?? '';
@endphp

@if (! empty($url))
    <a href="{{ $url }}" class="fm-badge{{ $tone === 'muted' ? ' fm-badge--muted' : '' }}">{{ $label }}</a>
@else
    <span class="fm-badge{{ $tone === 'muted' ? ' fm-badge--muted' : '' }}">{{ $label }}</span>
@endif
