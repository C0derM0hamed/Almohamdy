@php
    $closeUrl = $closeUrl ?? route('modules.doctors-admin.doctors.index');
    $modalIcon = $modalIcon ?? 'bi-person-badge';
@endphp

<div class="hm-doctors-admin-modal-page">
    <a href="{{ $closeUrl }}" class="hm-doctors-admin-modal-backdrop" aria-hidden="true" tabindex="-1"></a>

    <div class="hm-doctors-admin-modal-center">
        <div class="hm-doctors-admin-modal" role="dialog" aria-modal="true" aria-labelledby="doctorAdminModalTitle">
            <div class="hm-doctors-admin-modal__accent" aria-hidden="true"></div>

            <div class="hm-doctors-admin-modal__header">
                <div class="hm-doctors-admin-modal__header-main">
                    <span class="hm-doctors-admin-modal__badge" aria-hidden="true">
                        <i class="bi {{ $modalIcon }}"></i>
                    </span>
                    <div class="hm-doctors-admin-modal__header-text">
                        <h1 id="doctorAdminModalTitle" class="hm-doctors-admin-modal__title">{{ $modalTitle }}</h1>
                        @if (! empty($modalSubtitle))
                            <p class="hm-doctors-admin-modal__subtitle">{{ $modalSubtitle }}</p>
                        @endif
                    </div>
                </div>
                <a href="{{ $closeUrl }}" class="hm-doctors-admin-modal__close" aria-label="{{ __('doctors_directory_admin.close') }}">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </a>
            </div>

            <div class="hm-doctors-admin-modal__body hm-dda hm-dda--doctor-modal">
                @if (! empty($showSuccess) && session('success'))
                    <div class="hm-alert-success dda-form-flash">{{ session('success') }}</div>
                @endif
