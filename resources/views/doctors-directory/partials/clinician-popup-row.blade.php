@props([
    'label',
    'doctorId',
    'type',
    'doctor',
])

@php
    $hasContent = $type === 'qual' ? $doctor->hasQualification() : $doctor->hasCases();
    $html = $type === 'qual' ? $doctor->localizedQualificationHtml() : $doctor->localizedCasesHtml();
    $emptyMessage = $type === 'qual'
        ? __('doctors_directory.no_qualification_detail')
        : __('doctors_directory.no_cases_detail');
    $icon = $type === 'qual' ? 'bi-mortarboard' : 'bi-clipboard2-pulse';
@endphp

<article class="hm-clinician-popup-card hm-clinician-popup-card--detail">
    <div class="hm-clinician-popup-card__accent" aria-hidden="true"></div>

    @include('partials.hm-clinician-popup-header', [
        'title' => $label,
        'icon' => $icon,
        'closeLabel' => __('doctors_directory.close'),
    ])

    <div class="hm-clinician-popup-card__body">
        @if ($hasContent)
            <div class="hm-clinician-popup-card__text">{!! $html !!}</div>
        @else
            <p class="hm-clinician-modal__empty">{{ $emptyMessage }}</p>
        @endif
    </div>

    <footer class="hm-hope-modal__footer hm-clinician-popup-card__footer">
        <button type="button" class="btn btn-primary hm-clinician-popup-card__close-btn" data-hm-clinician-modal-close>
            {{ __('doctors_directory.close') }}
        </button>
    </footer>
</article>
