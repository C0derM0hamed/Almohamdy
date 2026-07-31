@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'closeLabel' => __('doctors_directory.close'),
])

<header class="hm-hope-modal__header hm-clinician-popup-card__head">
    <div class="hm-hope-modal__header-main hm-clinician-popup-card__head-copy">
        @if ($icon)
            <span class="hm-hope-modal__icon hm-clinician-popup-card__title-icon" aria-hidden="true">
                <i class="bi {{ $icon }}"></i>
            </span>
        @endif
        <div class="hm-hope-modal__titles">
            @if ($subtitle)
                <small class="hm-hope-modal__eyebrow hm-clinician-popup-card__eyebrow">{{ $subtitle }}</small>
            @endif
            <h3 class="hm-hope-modal__title hm-clinician-popup-card__title">{{ $title }}</h3>
        </div>
    </div>
    <button
        type="button"
        class="hm-hope-modal__close hm-clinician-popup-card__close-icon"
        data-hm-clinician-modal-close
        aria-label="{{ $closeLabel }}"
    >
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
</header>
