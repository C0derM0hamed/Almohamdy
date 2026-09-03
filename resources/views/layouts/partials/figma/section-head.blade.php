@php
    $title = $title ?? '';
    $countLabel = $countLabel ?? '';
    $icon = $icon ?? 'bi-grid';
@endphp

<div class="fm-section__head">
    <div class="fm-section__title">
        <span class="fm-section__icon" aria-hidden="true">
            @if (! empty($iconHtml))
                {!! $iconHtml !!}
            @else
                <i class="bi {{ $icon }}"></i>
            @endif
        </span>
        <h2>{{ $title }}</h2>
    </div>
    @if ($countLabel !== '')
        <span class="fm-count">{{ $countLabel }}</span>
    @endif
</div>
