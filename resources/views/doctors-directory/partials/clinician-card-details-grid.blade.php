@props([
    'doctor',
    'hospitalName',
    'clinicBuilding',
    'clinicNumber',
    'consultationFee',
    'ageGroup' => null,
    'showMoreButtons' => true,
])

@php($resolvedAgeGroup = $ageGroup ?? ($doctor->localizedAgeGroup() ?: '—'))

<div class="dd-doctor-details">
    <div class="dd-detail-item">
        <div class="dd-detail-icon"><i class="bi bi-award"></i></div>
        <div class="dd-detail-label">{{ __('doctors_directory.holds_qualification') }}</div>
        @if ($showMoreButtons)
            <button
                type="button"
                class="dd-more-btn hm-clinician-card__more-btn"
                data-hm-doctor-modal
                data-modal-target="qual-{{ $doctor->id }}"
                data-modal-title="{{ __('doctors_directory.qualification') }}"
                data-modal-empty="{{ __('doctors_directory.no_qualification_detail') }}"
                data-modal-close-label="{{ __('doctors_directory.close') }}"
            >{{ __('doctors_directory.more') }}</button>
        @elseif ($doctor->hasQualification())
            <div class="dd-detail-value">{!! $doctor->localizedQualificationHtml() !!}</div>
        @else
            <div class="dd-detail-value">—</div>
        @endif
    </div>

    <div class="dd-detail-item">
        <div class="dd-detail-icon"><i class="bi bi-cash-coin"></i></div>
        <div class="dd-detail-label">{{ __('doctors_directory.examination_fee') }}</div>
        <div class="dd-detail-value">{{ $consultationFee ? number_format($consultationFee) : '—' }}</div>
    </div>

    <div class="dd-detail-item">
        <div class="dd-detail-icon"><i class="bi bi-eye"></i></div>
        <div class="dd-detail-label">{{ __('doctors_directory.cases_seen') }}</div>
        @if ($showMoreButtons)
            <button
                type="button"
                class="dd-more-btn hm-clinician-card__more-btn"
                data-hm-doctor-modal
                data-modal-target="cases-{{ $doctor->id }}"
                data-modal-title="{{ __('doctors_directory.cases_seen') }}"
                data-modal-empty="{{ __('doctors_directory.no_cases_detail') }}"
                data-modal-close-label="{{ __('doctors_directory.close') }}"
            >{{ __('doctors_directory.more') }}</button>
        @elseif ($doctor->hasCases())
            <div class="dd-detail-value">{!! $doctor->localizedCasesHtml() !!}</div>
        @else
            <div class="dd-detail-value">—</div>
        @endif
    </div>

    <div class="dd-detail-item">
        <div class="dd-detail-icon"><i class="bi bi-building"></i></div>
        <div class="dd-detail-label">{{ __('doctors_directory.clinic_building') }}</div>
        <div class="dd-detail-value">{{ $clinicBuilding }}</div>
    </div>

    <div class="dd-detail-item">
        <div class="dd-detail-icon"><i class="bi bi-people"></i></div>
        <div class="dd-detail-label">{{ __('doctors_directory.age_group') }}</div>
        <div class="dd-detail-value">{{ $resolvedAgeGroup }}</div>
    </div>

    <div class="dd-detail-item">
        <div class="dd-detail-icon"><i class="bi bi-hash"></i></div>
        <div class="dd-detail-label">{{ __('doctors_directory.clinic_number') }}</div>
        <div class="dd-detail-value">{{ $clinicNumber }}</div>
    </div>

    <div class="dd-detail-item">
        <div class="dd-detail-icon"><i class="bi bi-hospital"></i></div>
        <div class="dd-detail-label">{{ __('doctors_directory.hospital') }}</div>
        <div class="dd-detail-value">{{ $hospitalName }}</div>
    </div>
</div>
