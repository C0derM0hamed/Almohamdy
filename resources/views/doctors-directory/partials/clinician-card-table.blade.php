@props([
    'doctor',
    'doctorDisplayName',
    'doctorSpeciality',
    'hospitalName',
    'clinicBuilding',
    'clinicNumber',
    'consultationFee',
    'showMoreButtons' => true,
    'linkName' => false,
    'doctorHasPersonName' => true,
    'doctorShowUrl' => null,
    'hideIdentityRows' => false,
])

<table class="hm-clinician-table">
    <tbody>
        @unless ($hideIdentityRows)
        <tr>
            <th scope="row">{{ __('doctors_directory.doctor_name') }}</th>
            <td>
                @if ($linkName && $doctorShowUrl && ($doctorHasPersonName ?? true))
                    <a href="{{ $doctorShowUrl }}" class="hm-clinician-card__name-link">{{ $doctorDisplayName }}</a>
                @else
                    {{ $doctorDisplayName }}
                @endif
            </td>
        </tr>
        <tr>
            <th scope="row">{{ __('doctors_directory.nationality') }}</th>
            <td>{{ $doctor->country?->localizedName() ?: '—' }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('doctors_directory.code') }}</th>
            <td>{{ $doctor->code ?: '—' }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('doctors_directory.specialization') }}</th>
            <td>{{ $doctorSpeciality ?: '—' }}</td>
        </tr>
        @endunless
        <tr>
            <th scope="row">{{ __('doctors_directory.holds_qualification') }}</th>
            <td>
                @if ($showMoreButtons)
                    <button
                        type="button"
                        class="hm-clinician-card__more-btn"
                        data-hm-doctor-modal
                        data-modal-target="qual-{{ $doctor->id }}"
                        data-modal-title="{{ __('doctors_directory.qualification') }}"
                        data-modal-empty="{{ __('doctors_directory.no_qualification_detail') }}"
                        data-modal-close-label="{{ __('doctors_directory.close') }}"
                    >
                        {{ __('doctors_directory.more') }}
                    </button>
                @elseif ($doctor->hasQualification())
                    <div class="hm-clinician-table__rich">{!! $doctor->localizedQualificationHtml() !!}</div>
                @else
                    —
                @endif
            </td>
        </tr>
        <tr>
            <th scope="row">{{ __('doctors_directory.cases_seen') }}</th>
            <td>
                @if ($showMoreButtons)
                    <button
                        type="button"
                        class="hm-clinician-card__more-btn"
                        data-hm-doctor-modal
                        data-modal-target="cases-{{ $doctor->id }}"
                        data-modal-title="{{ __('doctors_directory.cases_seen') }}"
                        data-modal-empty="{{ __('doctors_directory.no_cases_detail') }}"
                        data-modal-close-label="{{ __('doctors_directory.close') }}"
                    >
                        {{ __('doctors_directory.more') }}
                    </button>
                @elseif ($doctor->hasCases())
                    <div class="hm-clinician-table__rich">{!! $doctor->localizedCasesHtml() !!}</div>
                @else
                    —
                @endif
            </td>
        </tr>
        <tr>
            <th scope="row">{{ __('doctors_directory.age_group') }}</th>
            <td>{{ $doctor->localizedAgeGroup() ?: '—' }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('doctors_directory.hospital') }}</th>
            <td>{{ $hospitalName }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('doctors_directory.examination_fee') }}</th>
            <td>{{ $consultationFee ? number_format($consultationFee) : '—' }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('doctors_directory.clinic_building') }}</th>
            <td>{{ $clinicBuilding }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('doctors_directory.clinic_number') }}</th>
            <td>{{ $clinicNumber }}</td>
        </tr>
    </tbody>
</table>
