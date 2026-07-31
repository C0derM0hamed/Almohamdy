@extends('layouts.app')

@section('title', $doctor->localizedDisplayName())

@section('sidebar_heading', __('doctors_directory.title'))
@section('sidebar_subheading', __('doctors_directory.doctors_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-doctors-redesign.css') }}?v={{ filemtime(public_path('css/hm-doctors-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-doctors-directory.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory.css')) }}" rel="stylesheet">
@endpush

@section('content')
    @php
        $scheduleRows = $doctor->holidayOffers
            ->flatMap(fn ($offer) => $offer->workingDays)
            ->unique(fn ($row) => $row->day.'|'.$row->time_from.'|'.$row->time_to)
            ->sortBy([
                ['day', 'asc'],
                ['time_from', 'asc'],
            ])
            ->values();

        $doctorHeroName = $doctor->localizedDisplayName();
        $doctorHeroSecondaryName = $doctor->localizedSecondaryName();
        $doctorHeroSpeciality = $doctor->localizedSpecialization()
            ?: ($doctor->speciality?->localizedName() ?? '');

        $hasProfilePanel = $doctor->country || $doctor->localizedAgeGroup();
        $hasProfessionalPanel = $doctor->localizedSpecialization() || $doctor->localizedQualification();
        $hasCasesPanel = (bool) $doctor->localizedCases();
        $hasContactPanel = $doctor->mobile || $doctor->email;

        $breadcrumbItems = [];

        if (! empty($serviceLocationContext)) {
            $breadcrumbItems = [
                ['label' => __('service_locations.title'), 'url' => route('modules.service-locations.index')],
                ['label' => $opdLabel, 'url' => $opdShowRoute],
                ['label' => $departmentName, 'url' => $doctorsListRoute],
                ['label' => $doctorHeroName, 'chip' => true],
            ];
        } else {
            $breadcrumbItems = [
                ['label' => __('doctors_directory.title'), 'url' => route('modules.doctors.specialities.index')],
                ['label' => __('doctors_directory.specialities'), 'url' => route('modules.doctors.specialities.index')],
            ];

            if ($doctor->speciality) {
                $breadcrumbItems[] = [
                    'label' => $doctor->speciality->localizedName(),
                    'url' => route('modules.doctors.specialities.departments', $doctor->speciality->id),
                ];
            }

            $breadcrumbItems[] = ['label' => $doctorHeroName, 'chip' => true];
        }
    @endphp

    <div class="hm-dd hm-dd--doctor-show">
        @include('doctors-directory.partials.dd-breadcrumb', [
            'variant' => 'bar',
            'items' => $breadcrumbItems,
        ])

        <section class="dd-doctor-hero" aria-labelledby="ddDoctorHeroName">
            <div class="dd-doctor-hero__photo{{ $doctor->photoUrl() ? '' : ' is-placeholder' }}">
                @if ($doctor->photoUrl())
                    <img
                        src="{{ $doctor->photoUrl() }}"
                        alt="{{ $doctorHeroName }}"
                        width="148"
                        height="148"
                        loading="lazy"
                        decoding="async"
                        onerror="var wrap=this.closest('.dd-doctor-hero__photo'); this.remove(); wrap.classList.add('is-placeholder');"
                    >
                @else
                    <span class="dd-doctor-hero__photo-fallback" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                @endif
            </div>

            <div class="dd-doctor-hero__copy">
                <h1 id="ddDoctorHeroName" class="dd-doctor-hero__name">{{ $doctorHeroName }}</h1>
                @if ($doctorHeroSecondaryName !== '')
                    <p class="dd-doctor-hero__name-alt">{{ $doctorHeroSecondaryName }}</p>
                @endif

                <div class="dd-doctor-hero__chips">
                    @if ($doctor->code)
                        <span class="dd-doctor-chip dd-doctor-chip--code">
                            <i class="bi bi-hash" aria-hidden="true"></i>
                            {{ $doctor->code }}
                        </span>
                    @endif
                    @if ($doctorHeroSpeciality !== '')
                        <span class="dd-doctor-chip dd-doctor-chip--primary">
                            <i class="bi bi-heart-pulse" aria-hidden="true"></i>
                            {{ $doctorHeroSpeciality }}
                        </span>
                    @endif
                    @if ($doctor->country)
                        <span class="dd-doctor-chip">
                            <i class="bi bi-globe2" aria-hidden="true"></i>
                            {{ $doctor->country->localizedName() }}
                        </span>
                    @endif
                    @if ($doctor->price)
                        <span class="dd-doctor-chip dd-doctor-chip--fee">
                            <i class="bi bi-cash-coin" aria-hidden="true"></i>
                            {{ number_format($doctor->price) }} {{ __('doctors_directory.currency') }}
                        </span>
                    @endif
                    @if ($doctor->mobile)
                        <a href="tel:{{ $doctor->mobile }}" class="dd-doctor-chip dd-doctor-chip--contact">
                            <i class="bi bi-telephone" aria-hidden="true"></i>
                            {{ $doctor->mobile }}
                        </a>
                    @endif
                </div>
            </div>
        </section>

        @if ($hasProfilePanel || $hasProfessionalPanel || $hasContactPanel)
            <div class="dd-doctor-panels dd-doctor-panels--top">
                @if ($hasProfilePanel)
                    <article class="dd-doctor-panel">
                        <header class="dd-doctor-panel__head">
                            <span class="dd-doctor-panel__icon" aria-hidden="true"><i class="bi bi-person-vcard"></i></span>
                            <h2>{{ __('doctors_directory.sections.profile') }}</h2>
                        </header>
                        <div class="dd-doctor-panel__rows">
                            @if ($doctor->country)
                                <div class="dd-doctor-row">
                                    <span class="dd-doctor-row__label">{{ __('doctors_directory.nationality') }}</span>
                                    <span class="dd-doctor-row__value">{{ $doctor->country->localizedName() }}</span>
                                </div>
                            @endif
                            @if ($doctor->localizedAgeGroup())
                                <div class="dd-doctor-row">
                                    <span class="dd-doctor-row__label">{{ __('doctors_directory.age_group') }}</span>
                                    <span class="dd-doctor-row__value">{{ $doctor->localizedAgeGroup() }}</span>
                                </div>
                            @endif
                        </div>
                    </article>
                @endif

                @if ($hasProfessionalPanel)
                    <article class="dd-doctor-panel">
                        <header class="dd-doctor-panel__head">
                            <span class="dd-doctor-panel__icon dd-doctor-panel__icon--green" aria-hidden="true"><i class="bi bi-briefcase"></i></span>
                            <h2>{{ __('doctors_directory.sections.professional') }}</h2>
                        </header>
                        <div class="dd-doctor-panel__rows">
                            @if ($doctor->localizedSpecialization())
                                <div class="dd-doctor-row">
                                    <span class="dd-doctor-row__label">{{ __('doctors_directory.specialization') }}</span>
                                    <span class="dd-doctor-row__value">{{ $doctor->localizedSpecialization() }}</span>
                                </div>
                            @endif
                            @if ($doctor->localizedQualification())
                                <div class="dd-doctor-row dd-doctor-row--stacked">
                                    <span class="dd-doctor-row__label">{{ __('doctors_directory.qualification') }}</span>
                                    <span class="dd-doctor-row__value dd-doctor-row__value--block">{{ $doctor->localizedQualification() }}</span>
                                </div>
                            @endif
                        </div>
                    </article>
                @endif

                @if ($hasContactPanel)
                    <article class="dd-doctor-panel">
                        <header class="dd-doctor-panel__head">
                            <span class="dd-doctor-panel__icon dd-doctor-panel__icon--teal" aria-hidden="true"><i class="bi bi-chat-dots"></i></span>
                            <h2>{{ __('doctors_directory.sections.contact') }}</h2>
                        </header>
                        <div class="dd-doctor-panel__rows">
                            @if ($doctor->mobile)
                                <div class="dd-doctor-row">
                                    <span class="dd-doctor-row__label">{{ __('doctors_directory.mobile') }}</span>
                                    <span class="dd-doctor-row__value">
                                        <a href="tel:{{ $doctor->mobile }}" class="dd-doctor-row__link">{{ $doctor->mobile }}</a>
                                    </span>
                                </div>
                            @endif
                            @if ($doctor->email)
                                <div class="dd-doctor-row">
                                    <span class="dd-doctor-row__label">{{ __('doctors_directory.email') }}</span>
                                    <span class="dd-doctor-row__value">
                                        <a href="mailto:{{ $doctor->email }}" class="dd-doctor-row__link">{{ $doctor->email }}</a>
                                    </span>
                                </div>
                            @endif
                        </div>
                    </article>
                @endif
            </div>
        @endif

        @if ($hasCasesPanel)
            <article class="dd-doctor-panel dd-doctor-panel--wide">
                <header class="dd-doctor-panel__head">
                    <span class="dd-doctor-panel__icon dd-doctor-panel__icon--purple" aria-hidden="true"><i class="bi bi-clipboard2-pulse"></i></span>
                    <h2>{{ __('doctors_directory.cases') }}</h2>
                </header>
                <div class="dd-doctor-panel__body-text">{{ $doctor->localizedCases() }}</div>
            </article>
        @endif

        <article class="dd-doctor-panel dd-doctor-panel--schedule">
            <header class="dd-doctor-panel__head">
                <span class="dd-doctor-panel__icon dd-doctor-panel__icon--schedule" aria-hidden="true"><i class="bi bi-calendar-week"></i></span>
                <h2>{{ __('doctors_directory.working_schedule') }}</h2>
            </header>

            @if ($scheduleRows->isNotEmpty())
                <div class="dd-doctor-schedule-wrap">
                    <table class="dd-doctor-schedule">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('doctors_directory.schedule_day') }}</th>
                                <th scope="col">{{ __('doctors_directory.schedule_time_from') }}</th>
                                <th scope="col">{{ __('doctors_directory.schedule_time_to') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($scheduleRows as $slot)
                                <tr>
                                    <td>{{ $slot->localizedDay() }}</td>
                                    <td>{{ $slot->formattedTimeFrom() }}</td>
                                    <td>{{ $slot->formattedTimeTo() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="dd-doctor-empty">
                    <i class="bi bi-calendar-x" aria-hidden="true"></i>
                    <p>{{ __('doctors_directory.no_schedule') }}</p>
                </div>
            @endif
        </article>
    </div>
@endsection
