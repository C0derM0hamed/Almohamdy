@php
    $closeUrl = $closeUrl ?? route('modules.leave.requests.index');
@endphp

<div class="hm-leave-modal-page">
    <a href="{{ $closeUrl }}" class="hm-leave-modal-backdrop" aria-hidden="true" tabindex="-1"></a>

    <div class="hm-leave-modal-center">
        <div class="hm-leave-modal" role="dialog" aria-modal="true" aria-labelledby="leaveModalTitle">
            <div class="hm-leave-modal__accent" aria-hidden="true"></div>

            <div class="hm-leave-modal__header">
                <div class="hm-leave-modal__header-main">
                    <div class="hm-leave-modal__icon" aria-hidden="true">
                        <i class="bi bi-calendar-plus"></i>
                    </div>
                    <div class="hm-leave-modal__header-text">
                        <h1 id="leaveModalTitle" class="hm-leave-modal__title">{{ $modalTitle }}</h1>
                        @if (! empty($modalSubtitle))
                            <p class="hm-leave-modal__subtitle">{{ $modalSubtitle }}</p>
                        @endif
                    </div>
                </div>
                <a href="{{ $closeUrl }}" class="hm-leave-modal__close" aria-label="{{ __('employee_leave.cancel') }}">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </a>
            </div>

            <div class="hm-leave-modal__body">
