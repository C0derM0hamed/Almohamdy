@php
    $variant = $variant ?? 'primary';
    $label = $label ?? '';
    $value = $value ?? '';
    $icon = $icon ?? 'bi-circle';
    $url = $url ?? '';
    $tag = $url !== '' ? 'a' : 'div';
    $isActive = (bool) ($isActive ?? false);
@endphp

<{{ $tag }}
    @if ($url !== '') href="{{ $url }}" @endif
    class="fm-stat fm-stat--fill fm-stat--{{ $variant }}{{ $isActive ? ' is-active' : '' }}"
>
    <div class="fm-stat__top">
        <span class="fm-stat__label">{{ $label }}</span>
        <span class="fm-stat__icon" aria-hidden="true">
            @if (! empty($iconHtml))
                {!! $iconHtml !!}
            @else
                <i class="bi {{ $icon }}"></i>
            @endif
        </span>
    </div>
    <span class="fm-stat__value">{{ $value }}</span>
</{{ $tag }}>
