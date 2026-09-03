@php
    $compact = (bool) ($compact ?? false);
    $heroIcon = $heroIcon ?? 'bi-grid';
    $heroIconSrc = $heroIconSrc ?? '';
    $heroIconSize = (int) ($heroIconSize ?? 26);
    $actionUrl = $actionUrl ?? null;
    $actionModal = $actionModal ?? null;
    $actionLabel = $actionLabel ?? null;
    $actionIcon = $actionIcon ?? 'bi-plus-lg';
    $actionIconSrc = $actionIconSrc ?? '';
@endphp

<header class="fm-head {{ $compact ? 'fm-head--compact' : '' }}">
    <div class="fm-head__row">
        <div class="fm-head__page">
            <div class="hm-figma-crumb-row">
                @include('layouts.partials.figma-sidebar-toggle')
                <nav class="fm-crumb" aria-label="{{ __('breadcrumbs.aria_label') }}">
                @foreach ($crumbs as $index => $crumb)
                    @if ($index > 0)
                        <img class="fm-crumb__sep" src="{{ asset('images/figma/header/crumb-sep.svg') }}" alt="" width="18" height="18">
                    @endif
                    @if ($index === array_key_last($crumbs))
                        <span class="fm-crumb__current">{{ $crumb['label'] }}</span>
                    @elseif (! empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                    @else
                        <span>{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
                </nav>
            </div>

            @unless ($compact)
                <div class="fm-hero {{ ($actionUrl || $actionModal) && $actionLabel ? 'fm-hero--split' : '' }}">
                    <div class="fm-hero__icon" aria-hidden="true">
                        @if ($heroIconSrc !== '')
                            <img src="{{ $heroIconSrc }}" alt="" width="{{ $heroIconSize }}" height="{{ $heroIconSize }}">
                        @else
                            <i class="bi {{ $heroIcon }}"></i>
                        @endif
                    </div>
                    <div class="fm-hero__copy">
                        <h1>{{ $title }}</h1>
                        @if (! empty($subtitle))
                            <p>{{ $subtitle }}</p>
                        @endif
                    </div>
                    @if ($actionUrl && $actionLabel)
                        <a class="fm-btn fm-btn--primary fm-hero__action" href="{{ $actionUrl }}">
                            @if ($actionIconSrc !== '')
                                <img src="{{ $actionIconSrc }}" alt="" width="18" height="18">
                            @else
                                <i class="bi {{ $actionIcon }}" aria-hidden="true"></i>
                            @endif
                            {{ $actionLabel }}
                        </a>
                    @elseif ($actionModal && $actionLabel)
                        <button class="fm-btn fm-btn--primary fm-hero__action" type="button" data-bs-toggle="modal" data-bs-target="{{ $actionModal }}">
                            @if ($actionIconSrc !== '')
                                <img src="{{ $actionIconSrc }}" alt="" width="18" height="18">
                            @else
                                <i class="bi {{ $actionIcon }}" aria-hidden="true"></i>
                            @endif
                            {{ $actionLabel }}
                        </button>
                    @endif
                </div>
            @endunless
        </div>

        @include('layouts.partials.figma-header-tools')
    </div>
</header>
