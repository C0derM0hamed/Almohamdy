<header class="cc-list-head">
    <div class="cc-list-head__title">
        <h2>{{ $title }}</h2>
        <span>{{ number_format((int) $count) }} {{ $countLabel }}</span>
    </div>
    <div class="cc-list-head__tools" aria-label="{{ $title }}">
        <button type="button"><i class="bi bi-arrow-down-up" aria-hidden="true"></i><span>{{ __('government_circulars.toolbar.sort') }}</span></button>
        <button type="button" onclick="window.location.reload()" aria-label="{{ __('government_circulars.toolbar.refresh') }}"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i></button>
        <button type="button" onclick="window.print()"><i class="bi bi-download" aria-hidden="true"></i><span>{{ __('government_circulars.toolbar.export') }}</span></button>
    </div>
</header>
