@php
    $isLink = ! empty($url);
    $tag = $isLink ? 'a' : 'span';
    $countLabel = $countLabel ?? '';
@endphp

<{{ $tag }}
    @if ($isLink) href="{{ $url }}" @endif
    class="hs-dash-card"
    @if (! empty($searchText)) data-hs-dash-card data-search-text="{{ $searchText }}" @endif
>
    <div class="hs-dash-card__content">
        <div class="hs-dash-card__icon" aria-hidden="true">
            <i class="bi {{ $icon ?? 'bi-grid' }}"></i>
        </div>
        <h2 class="hs-dash-card__title">{{ $title }}</h2>
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
