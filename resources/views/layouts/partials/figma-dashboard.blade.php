@php
    $stats = $stats ?? [];
    $cards = $cards ?? [];
    $actions = $actions ?? [];
    $breadcrumbs = $breadcrumbs ?? [];
    $pageClass = $pageClass ?? '';
    $searchId = $searchId ?? 'servicesDashboardSearch';
    $gridId = $gridId ?? 'servicesDashboardGrid';
    $searchButtonId = $searchButtonId ?? 'serviceLocationsSearchBtn';
    $emptyMessage = $emptyMessage ?? '';
    $cardActionLabel = $cardActionLabel ?? '';
    $arrowIcon = app()->getLocale() === 'ar' ? 'bi-arrow-left' : 'bi-arrow-right';
@endphp

<div class="hm-dd hm-dd--figma {{ $pageClass }}">
    <header class="dd-figma-head">
        <div class="dd-figma-head__row">
            <div class="dd-figma-head__page">
                <div class="hm-figma-crumb-row">
                    @include('layouts.partials.figma-sidebar-toggle')
                    @include('doctors-directory.partials.dd-breadcrumb', [
                        'variant' => 'plain',
                        'items' => $breadcrumbs,
                    ])
                </div>

                <div class="dd-figma-hero">
                    <div class="dd-figma-hero__icon" aria-hidden="true">
                        <i class="bi {{ $heroIcon ?? 'bi-grid' }}"></i>
                    </div>
                    <div class="dd-figma-hero__copy">
                        <h1>{{ $title }}</h1>
                        <p>{{ $subtitle }}</p>
                    </div>
                </div>
            </div>

            @include('layouts.partials.figma-header-tools')
        </div>
    </header>

    @if (count($stats) > 0)
        <div class="dd-figma-stats">
            @foreach ($stats as $stat)
                <{{ ! empty($stat['url']) ? 'a' : 'div' }}
                    @if (! empty($stat['url'])) href="{{ $stat['url'] }}" @endif
                    class="dd-figma-stat dd-figma-stat--{{ $stat['variant'] ?? 'primary' }} {{ ! empty($stat['available']) ? 'dd-figma-stat--available' : '' }}"
                >
                    <div class="dd-figma-stat__icon" aria-hidden="true">
                        <i class="bi {{ $stat['icon'] ?? 'bi-grid' }}"></i>
                    </div>
                    <div class="dd-figma-stat__copy">
                        <small>{{ $stat['label'] }}</small>
                        <b>{{ $stat['value'] }}</b>
                        @if (! empty($stat['hint']))
                            <p>{{ $stat['hint'] }}</p>
                        @endif
                    </div>
                </{{ ! empty($stat['url']) ? 'a' : 'div' }}>
            @endforeach
        </div>
    @endif

    @if (count($cards) > 0)
        <section class="dd-figma-filters" aria-labelledby="{{ $searchId }}FiltersTitle">
            <div class="dd-figma-filters__head">
                <h2 id="{{ $searchId }}FiltersTitle">{{ $filterTitle }}</h2>
                @if (! empty($filterSubtitle))
                    <p>{{ $filterSubtitle }}</p>
                @endif
            </div>
            <div class="dd-figma-filters__row">
                <label class="dd-figma-search" for="{{ $searchId }}">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="{{ $searchId }}"
                        placeholder="{{ $searchPlaceholder }}"
                        aria-label="{{ $searchPlaceholder }}"
                        autocomplete="off"
                        enterkeyhint="search"
                    >
                </label>

                <button type="button" id="{{ $searchButtonId }}" class="dd-figma-btn dd-figma-btn--search">
                    {{ $searchLabel }}
                </button>

                <button type="button" class="dd-figma-btn dd-figma-btn--reset" data-hm-dashboard-reset="{{ $searchId }}">
                    {{ $resetLabel }}
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                </button>
            </div>
        </section>

        <section class="dd-figma-section" aria-labelledby="{{ $gridId }}Title">
            <div class="dd-figma-section__head">
                <div class="dd-figma-section__title">
                    <span class="dd-figma-section__icon" aria-hidden="true">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </span>
                    <div>
                        <h2 id="{{ $gridId }}Title">{{ $sectionTitle }}</h2>
                        @if (! empty($sectionSubtitle))
                            <p class="dd-figma-section__subtitle">{{ $sectionSubtitle }}</p>
                        @endif
                    </div>
                </div>
                <span class="dd-figma-count">
                    <span data-hm-dashboard-count-for="{{ $gridId }}">{{ count($cards) }}</span>
                    {{ $countLabel }}
                </span>
            </div>

            <div id="{{ $gridId }}" class="dd-figma-grid">
                @foreach ($cards as $card)
                    <a
                        href="{{ $card->url }}"
                        class="dd-figma-card js-dashboard-card"
                        data-hs-dash-card
                        data-search-text="{{ strtolower($card->title.' '.$card->description) }}"
                    >
                        <span class="dd-figma-card__icon" aria-hidden="true">
                            <i class="bi {{ $card->icon }}"></i>
                        </span>
                        <h3>{{ $card->title }}</h3>
                        <span class="dd-figma-card__meta">
                            <span class="dd-figma-card__dot" aria-hidden="true"></span>
                            <span>{{ $card->description }}</span>
                        </span>
                        <span class="dd-figma-card__action">
                            <i class="bi {{ $arrowIcon }}" aria-hidden="true"></i>
                            {{ $cardActionLabel }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @elseif ($emptyMessage !== '')
        <div class="dd-figma-empty">{{ $emptyMessage }}</div>
    @endif

    @if (count($actions) > 0)
        <nav class="dd-figma-dashboard-actions" aria-label="{{ $sectionTitle }}">
            @foreach ($actions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="dd-figma-dashboard-action {{ ! empty($action['primary']) ? 'is-primary' : '' }}"
                    @if (! empty($action['external'])) target="_blank" rel="noopener noreferrer" @endif
                >
                    <i class="bi {{ $action['icon'] ?? 'bi-arrow-left' }}" aria-hidden="true"></i>
                    {{ $action['label'] }}
                </a>
            @endforeach
        </nav>
    @endif
</div>
