@props([
    'doctor',
    'doctorDisplayName',
    'doctorSpeciality',
    'linkName' => false,
    'doctorHasPersonName' => true,
    'doctorShowUrl' => null,
    'variant' => 'default',
])

<div class="hm-clinician-card__header{{ $variant === 'v2' ? ' hm-clinician-card__header--v2' : '' }}">
    <div class="hm-clinician-card__photo{{ $doctor->photoUrl() ? '' : ' is-placeholder' }}{{ $variant === 'v2' ? ' hm-clinician-card__photo--v2' : '' }}">
        @if ($doctor->photoUrl())
            <img
                src="{{ $doctor->photoUrl() }}"
                alt="{{ $doctorDisplayName }}"
                class="hm-clinician-card__photo-img"
                width="96"
                height="96"
                loading="lazy"
                decoding="async"
                onerror="var wrap=this.closest('.hm-clinician-card__photo'); this.remove(); wrap.classList.add('is-placeholder'); var ph=wrap.querySelector('.hm-clinician-card__photo-placeholder'); if(ph) ph.hidden=false;"
            >
        @endif
        <span class="hm-clinician-card__photo-placeholder" aria-hidden="true" @if ($doctor->photoUrl()) hidden @endif>
            <i class="bi bi-person-badge"></i>
        </span>
    </div>

    <div class="hm-clinician-card__identity">
        <h3 class="hm-clinician-card__name">
            @if ($linkName && $doctorShowUrl && ($doctorHasPersonName ?? true))
                <a href="{{ $doctorShowUrl }}" class="hm-clinician-card__name-link">{{ $doctorDisplayName }}</a>
            @else
                {{ $doctorDisplayName }}
            @endif
        </h3>

        @if ($doctorSpeciality)
            <p class="hm-clinician-card__speciality">{{ $doctorSpeciality }}</p>
        @endif

        <div class="hm-clinician-card__meta">
            @if ($doctor->code)
                <span class="hm-clinician-card__meta-item">
                    <i class="bi bi-person-badge" aria-hidden="true"></i>
                    {{ $doctor->code }}
                </span>
            @endif
            @if ($doctor->country?->localizedName())
                <span class="hm-clinician-card__meta-item">
                    <i class="bi bi-globe2" aria-hidden="true"></i>
                    {{ $doctor->country->localizedName() }}
                </span>
            @endif
        </div>
    </div>
</div>
