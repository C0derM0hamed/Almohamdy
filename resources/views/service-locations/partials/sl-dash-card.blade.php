@php
    $isLink = ! empty($url);
    $tag = $isLink ? 'a' : 'span';
    $headingLevel = $headingLevel ?? 2;
    $countLabel = $countLabel ?? '';
@endphp

<{{ $tag }}
    @if ($isLink) href="{{ $url }}" @endif
    class="hs-dash-card{{ ! $isLink ? ' is-static' : '' }}"
    @if (! empty($searchText)) data-hs-dash-card data-search-text="{{ $searchText }}" @endif
    @if (! $isLink) aria-disabled="true" tabindex="-1" @endif
>
    <div class="hs-dash-card__content">
        <div class="hs-dash-card__icon" aria-hidden="true">
            <i class="bi {{ $icon ?? 'bi-building' }}"></i>
        </div>
        <h{{ $headingLevel }} class="hs-dash-card__title">{{ $title }}</h{{ $headingLevel }}>
        <span class="hs-dash-card__line" aria-hidden="true"></span>
        @if (! empty($description))
            <p class="hs-dash-card__desc">{{ $description }}</p>
        @endif
    </div>
    <div class="hs-dash-card__bottom">
        @if ($countLabel !== '')
            <span class="hs-dash-card__count">{{ $countLabel }}</span>
        @else
            <span class="hs-dash-card__count">&nbsp;</span>
        @endif
        @if ($isLink)
            <span class="hs-dash-card__arrow" aria-hidden="true">
                <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-arrow-left' : 'bi-arrow-right' }}"></i>
            </span>
        @endif
    </div>
</{{ $tag }}>
