@php
    $title = $title ?? '';
    $code = $code ?? '';
    $price = $price ?? '';
    $currency = $currency ?? '';
    $duration = $duration ?? '';
    $description = $description ?? '';
    $note = $note ?? '';
    $detailsUrl = $detailsUrl ?? '';
    $goUrl = $goUrl ?? $detailsUrl;
@endphp

<article class="fm-pkg">
    <header class="fm-pkg__hero">
        <div class="fm-pkg__tools">
            <button type="button" class="fm-pkg__more" aria-label="{{ app()->getLocale() === 'ar' ? 'المزيد' : 'More' }}">
                <img src="{{ asset('images/figma/system/pkg-more.svg') }}" alt="" width="18" height="18">
            </button>
            <span class="fm-pkg__mark" aria-hidden="true">
                @if (! empty($markHtml))
                    {!! $markHtml !!}
                @else
                    <img src="{{ asset('images/figma/system/pkg-desc.svg') }}" alt="" width="20" height="20">
                @endif
            </span>
        </div>
        <h3 class="fm-pkg__title">{{ $title }}</h3>
        @if ($code !== '')
            <span class="fm-pkg__code">{{ $code }}</span>
        @endif
    </header>

    <div class="fm-pkg__body">
        <div class="fm-pkg__meta">
            <div class="fm-pkg__meta-item">
                <span class="fm-pkg__label">{{ $durationLabel ?? '' }}</span>
                <span class="fm-pkg__value">
                    <img src="{{ asset('images/figma/system/pkg-clock.svg') }}" alt="" width="15" height="15">
                    {{ $duration }}
                </span>
            </div>
            <div class="fm-pkg__meta-item">
                <span class="fm-pkg__label">{{ $priceLabel ?? '' }}</span>
                <span class="fm-pkg__value">
                    <span class="fm-pkg__price">{{ $price }}</span>
                    <span class="fm-pkg__currency">{{ $currency }}</span>
                </span>
            </div>
        </div>

        @if ($description !== '')
            <div>
                <div class="fm-pkg__desc-head">
                    <span class="fm-icon-20">
                        <img src="{{ asset('images/figma/system/pkg-desc.svg') }}" alt="" width="12" height="12">
                    </span>
                    {{ $descriptionLabel ?? '' }}
                </div>
                <p class="fm-pkg__desc">{{ $description }}</p>
            </div>
        @endif

        @if ($note !== '')
            <div class="fm-pkg__note">
                <p class="fm-pkg__note-title">
                    <span class="fm-icon-22">
                        <img src="{{ asset('images/figma/system/pkg-note.svg') }}" alt="" width="12" height="12">
                    </span>
                    {{ $noteLabel ?? '' }}
                </p>
                <p>{{ $note }}</p>
            </div>
        @endif

        <div class="fm-pkg__foot">
            @if ($goUrl !== '')
                <a class="fm-btn--icon" href="{{ $goUrl }}" aria-hidden="true">
                    <img src="{{ asset('images/figma/system/pkg-arrow.svg') }}" alt="" width="18" height="18">
                </a>
            @endif
            @if ($detailsUrl !== '')
                <a class="fm-btn--ghost" href="{{ $detailsUrl }}">
                    {{ $detailsLabel ?? '' }}
                    <img src="{{ asset('images/figma/system/pkg-eye.svg') }}" alt="" width="17" height="17">
                </a>
            @endif
        </div>
    </div>
</article>
