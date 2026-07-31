@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-employee-leave.css') }}?v={{ filemtime(public_path('css/hm-employee-leave.css')) }}" rel="stylesheet">
@endpush

@section('title', __('employee_leave.new_request'))

@section('sidebar_heading', __('employee_services.title'))
@section('sidebar_subheading', __('employee_leave.new_request_subtitle'))

@section('content')
    @include('employee-leave.requests._modal-start', [
        'modalTitle' => __('employee_leave.new_request'),
        'modalSubtitle' => __('employee_leave.new_request_subtitle'),
        'closeUrl' => route('modules.leave.requests.index'),
    ])

    <form method="POST" action="{{ route('modules.leave.requests.store') }}" class="hm-leave-modal-form">
        @csrf

        <div class="hm-leave-modal-form__group">
            <label for="leave_type" class="hm-leave-modal-form__label">
                {{ __('employee_leave.fields.leave_type') }}
            </label>
            <div class="hm-leave-modal-form__input-wrap">
                <span class="hm-leave-modal-form__input-icon" aria-hidden="true">
                    <i class="bi bi-tag"></i>
                </span>
                <select id="leave_type" name="leave_type" class="hm-leave-modal-form__control hm-leave-modal-form__control--select @error('leave_type') is-invalid @enderror" required>
                    <option value="">{{ __('employee_leave.select_leave_type') }}</option>
                    @foreach ($leaveTypes as $typeId => $typeLabel)
                        <option value="{{ $typeId }}" @selected((string) old('leave_type') === (string) $typeId)>{{ $typeLabel }}</option>
                    @endforeach
                </select>
            </div>
            @error('leave_type')
                <div class="hm-leave-modal-form__error">{{ $message }}</div>
            @enderror
        </div>

        <div
            id="leaveTypeOtherGroup"
            class="hm-leave-modal-form__group hm-leave-modal-form__group--other"
            @if ((string) old('leave_type') !== (string) $otherLeaveTypeId) hidden @endif
        >
            <label for="leave_type_other" class="hm-leave-modal-form__label">
                {{ __('employee_leave.fields.leave_type_other') }}
            </label>
            <div class="hm-leave-modal-form__input-wrap">
                <span class="hm-leave-modal-form__input-icon" aria-hidden="true">
                    <i class="bi bi-pencil-square"></i>
                </span>
                <input
                    type="text"
                    id="leave_type_other"
                    name="leave_type_other"
                    value="{{ old('leave_type_other') }}"
                    class="hm-leave-modal-form__control @error('leave_type_other') is-invalid @enderror"
                    maxlength="100"
                    placeholder="{{ __('employee_leave.leave_type_other_placeholder') }}"
                >
            </div>
            @error('leave_type_other')
                <div class="hm-leave-modal-form__error">{{ $message }}</div>
            @enderror
        </div>

        <div class="hm-leave-modal-form__row">
            <div class="hm-leave-modal-form__group">
                <label for="start_date" class="hm-leave-modal-form__label">
                    {{ __('employee_leave.fields.start_date') }}
                </label>
                <div class="hm-leave-modal-form__input-wrap">
                    <span class="hm-leave-modal-form__input-icon" aria-hidden="true">
                        <i class="bi bi-calendar-event"></i>
                    </span>
                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="{{ old('start_date') }}"
                        class="hm-leave-modal-form__control hm-leave-modal-form__control--date @error('start_date') is-invalid @enderror"
                        required
                    >
                </div>
                @error('start_date')
                    <div class="hm-leave-modal-form__error">{{ $message }}</div>
                @enderror
            </div>

            <div class="hm-leave-modal-form__group">
                <label for="end_date" class="hm-leave-modal-form__label">
                    {{ __('employee_leave.fields.end_date') }}
                </label>
                <div class="hm-leave-modal-form__input-wrap">
                    <span class="hm-leave-modal-form__input-icon" aria-hidden="true">
                        <i class="bi bi-calendar-check"></i>
                    </span>
                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="{{ old('end_date') }}"
                        class="hm-leave-modal-form__control hm-leave-modal-form__control--date @error('end_date') is-invalid @enderror"
                        required
                    >
                </div>
                @error('end_date')
                    <div class="hm-leave-modal-form__error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="hm-leave-modal-form__group">
            <label for="reason" class="hm-leave-modal-form__label">
                {{ __('employee_leave.fields.reason') }}
            </label>
            <div class="hm-leave-modal-form__input-wrap hm-leave-modal-form__input-wrap--textarea">
                <span class="hm-leave-modal-form__input-icon" aria-hidden="true">
                    <i class="bi bi-chat-left-text"></i>
                </span>
                <textarea
                    id="reason"
                    name="reason"
                    class="hm-leave-modal-form__control hm-leave-modal-form__control--textarea @error('reason') is-invalid @enderror"
                    maxlength="200"
                    rows="5"
                    placeholder="{{ __('employee_leave.reason_placeholder') }}"
                    required
                >{{ old('reason') }}</textarea>
            </div>
            @error('reason')
                <div class="hm-leave-modal-form__error">{{ $message }}</div>
            @enderror
        </div>

        <div class="hm-leave-modal-form__actions">
            <a href="{{ route('modules.leave.requests.index') }}" class="btn hm-btn hm-btn--light">
                {{ __('employee_leave.cancel') }}
            </a>
            <button type="submit" class="btn hm-btn hm-btn--primary">
                <i class="bi bi-send" aria-hidden="true"></i>
                {{ __('employee_leave.submit') }}
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                var leaveTypeSelect = document.getElementById('leave_type');
                var otherGroup = document.getElementById('leaveTypeOtherGroup');
                var otherInput = document.getElementById('leave_type_other');
                var otherLeaveTypeId = @json($otherLeaveTypeId);

                if (!leaveTypeSelect || !otherGroup) {
                    return;
                }

                function toggleOtherLeaveTypeField() {
                    var isOther = String(leaveTypeSelect.value) === String(otherLeaveTypeId);
                    otherGroup.hidden = !isOther;

                    if (otherInput) {
                        otherInput.required = isOther;

                        if (!isOther) {
                            otherInput.value = '';
                        }
                    }
                }

                leaveTypeSelect.addEventListener('change', toggleOtherLeaveTypeField);
                toggleOtherLeaveTypeField();
            })();
        </script>
    @endpush

    @include('employee-leave.requests._modal-end')
@endsection
