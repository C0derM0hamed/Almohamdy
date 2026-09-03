@php
    $isLink = ! empty($url);
    $tag = $isLink ? 'a' : 'div';
    $titleTag = $titleTag ?? 'h3';
    $icon = $icon ?? 'bi-building';
    $actionLabel = $actionLabel ?? '';
    $countLabel = $countLabel ?? '';
    $searchText = $searchText ?? '';
    $iconSrc = $iconSrc ?? '';
    $iconHtml = $iconHtml ?? '';
    $actionIconSrc = $actionIconSrc ?? asset('images/figma/system/card-arrow.svg');
@endphp

<{{ $tag }}
    @if ($isLink) href="{{ $url }}" @endif
    class="fm-card{{ ! empty($empty) ? ' is-empty' : '' }}"
    @if (! $isLink) tabindex="0" role="group" @endif
    @if ($searchText !== '') data-hs-dash-card data-search-text="{{ $searchText }}" @endif
>
    <div class="fm-card__top">
        @if ($countLabel !== '')
            <span class="fm-card__badge">{{ $countLabel }}</span>
        @else
            <span></span>
        @endif
        <span class="fm-card__icon" aria-hidden="true">
            @if (! empty($iconHtml))
                {!! $iconHtml !!}
            @elseif (! empty($iconSrc))
                <img src="{{ $iconSrc }}" alt="" width="22" height="22">
            @else
                <i class="bi {{ $icon }}"></i>
            @endif
        </span>
    </div>
    <{{ $titleTag }}>{{ $title }}</{{ $titleTag }}>
    @if (! empty($description))
        <p class="fm-card__desc">
            <span class="fm-card__dot" aria-hidden="true"></span>
            <span>{{ $description }}</span>
        </p>
    @endif
    @if ($actionLabel !== '')
        <span class="fm-card__action">
            <img src="{{ $actionIconSrc }}" alt="" width="20" height="20">
            {{ $actionLabel }}
        </span>
    @endif
</{{ $tag }}>
