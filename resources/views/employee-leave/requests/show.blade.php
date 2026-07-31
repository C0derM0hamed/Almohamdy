@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-employee-leave.css') }}?v={{ filemtime(public_path('css/hm-employee-leave.css')) }}" rel="stylesheet">
@endpush

@section('title', __('employee_leave.request_detail'))

@section('sidebar_heading', __('employee_services.title'))
@section('sidebar_subheading', __('employee_leave.request_detail'))

@section('content')
    <div class="hm-el hm-el--detail">
        @include('employee-leave.partials.el-breadcrumb', [
            'items' => [
                ['label' => __('employee_services.title'), 'url' => route('modules.employee-services')],
                ['label' => __('employee_leave.requests'), 'url' => route('modules.leave.requests.index')],
                ['label' => '#'.$leave->id, 'chip' => true],
            ],
        ])

        @if (session('success'))
            <div class="hm-alert-success hm-leave-detail__alert">{{ session('success') }}</div>
        @endif

        <header class="el-detail-head">
            <div>
                <h1>{{ __('employee_leave.request_detail') }}</h1>
                <p>#{{ $leave->id }}</p>
            </div>
            <span class="hm-status-badge hm-status-badge--{{ $status }} hm-leave-detail__status">
                {{ __('employee_leave.status.'.$status) }}
            </span>
        </header>

    <div class="hm-leave-detail__grid">
        <div class="hm-leave-detail-card">
            <h2 class="hm-leave-detail-card__title">
                <span class="hm-leave-detail-card__icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                {{ __('employee_leave.sections.employee_info') }}
            </h2>
            <ul class="hm-detail-list">
                <li>
                    <span class="hm-detail-list__label">{{ __('employee_leave.fields.employee') }}</span>
                    <span class="hm-detail-list__value">{{ $leave->employeeDisplayName() }}</span>
                </li>
                @if ($leave->employee)
                    <li>
                        <span class="hm-detail-list__label">{{ __('employee_leave.fields.mobile') }}</span>
                        <span class="hm-detail-list__value">{{ $leave->employee->br_user_mobile ?: '—' }}</span>
                    </li>
                @endif
                <li>
                    <span class="hm-detail-list__label">{{ __('employee_leave.fields.email') }}</span>
                    <span class="hm-detail-list__value">{{ $leave->email ?: '—' }}</span>
                </li>
                <li>
                    <span class="hm-detail-list__label">{{ __('employee_leave.fields.job_title') }}</span>
                    <span class="hm-detail-list__value">{{ $leave->job_title ?: '—' }}</span>
                </li>
            </ul>
        </div>

        <div class="hm-leave-detail-card">
            <h2 class="hm-leave-detail-card__title">
                <span class="hm-leave-detail-card__icon" aria-hidden="true"><i class="bi bi-calendar2-week"></i></span>
                {{ __('employee_leave.sections.leave_details') }}
            </h2>
            <ul class="hm-detail-list">
                <li>
                    <span class="hm-detail-list__label">{{ __('employee_leave.fields.request_no') }}</span>
                    <span class="hm-detail-list__value">#{{ $leave->id }}</span>
                </li>
                <li>
                    <span class="hm-detail-list__label">{{ __('employee_leave.fields.leave_type') }}</span>
                    <span class="hm-detail-list__value">{{ $leave->leaveTypeLabel() }}</span>
                </li>
                <li>
                    <span class="hm-detail-list__label">{{ __('employee_leave.fields.start_date') }}</span>
                    <span class="hm-detail-list__value">{{ $leave->formattedStartDate() }}</span>
                </li>
                <li>
                    <span class="hm-detail-list__label">{{ __('employee_leave.fields.end_date') }}</span>
                    <span class="hm-detail-list__value">{{ $leave->formattedEndDate() }}</span>
                </li>
                <li>
                    <span class="hm-detail-list__label">{{ __('employee_leave.columns.days') }}</span>
                    <span class="hm-detail-list__value">{{ $leave->days }}</span>
                </li>
                <li class="hm-detail-list__item--stacked">
                    <span class="hm-detail-list__label">{{ __('employee_leave.fields.reason') }}</span>
                    <span class="hm-detail-list__value hm-detail-list__value--block">{{ $leave->applicationReason() ?: '—' }}</span>
                </li>
                <li>
                    <span class="hm-detail-list__label">{{ __('employee_leave.fields.submitted_at') }}</span>
                    <span class="hm-detail-list__value">
                        @php $submittedAt = (int) $leave->date; @endphp
                        {{ $submittedAt > 0 ? \Carbon\Carbon::createFromTimestamp($submittedAt)->format('Y-m-d H:i') : '—' }}
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <div class="hm-leave-detail__stack">
    @if ($canProcessBranch)
        @can('employee_leave.branch_process')
        <div class="hm-leave-detail-card hm-leave-detail-card--process">
            <h2 class="hm-leave-detail-card__title">
                <span class="hm-leave-detail-card__icon hm-leave-detail-card__icon--branch" aria-hidden="true"><i class="bi bi-building-check"></i></span>
                {{ __('employee_leave.sections.branch_processing') }}
            </h2>
            <p class="hm-leave-process__hint">{{ __('employee_leave.processing.branch_hint') }}</p>
            <form method="POST" action="{{ route('modules.leave.requests.branch.process', $leave->id) }}" class="hm-leave-process-form">
                @csrf
                <div class="hm-leave-process-form__field">
                    <label for="branch_comment" class="form-label">{{ __('employee_leave.fields.comment') }}</label>
                    <textarea
                        id="branch_comment"
                        name="comment"
                        class="form-control @error('comment') is-invalid @enderror"
                        rows="3"
                        maxlength="200"
                        placeholder="{{ __('employee_leave.processing.comment_placeholder') }}"
                    >{{ old('comment') }}</textarea>
                    @error('comment')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @error('decision')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="hm-leave-process-form__actions">
                    <button type="submit" name="decision" value="reject" class="btn hm-btn hm-btn--light hm-leave-process-form__btn--reject">
                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                        {{ __('employee_leave.processing.reject') }}
                    </button>
                    <button type="submit" name="decision" value="approve" class="btn hm-btn hm-btn--primary">
                        <i class="bi bi-check-circle" aria-hidden="true"></i>
                        {{ __('employee_leave.processing.approve') }}
                    </button>
                </div>
            </form>
        </div>
        @endcan
    @endif

    @if ($canProcessHr)
        @can('employee_leave.hr_process')
        <div class="hm-leave-detail-card hm-leave-detail-card--process">
            <h2 class="hm-leave-detail-card__title">
                <span class="hm-leave-detail-card__icon hm-leave-detail-card__icon--hr" aria-hidden="true"><i class="bi bi-person-check"></i></span>
                {{ __('employee_leave.sections.hr_processing') }}
            </h2>
            <p class="hm-leave-process__hint">{{ __('employee_leave.processing.hr_hint') }}</p>
            <form method="POST" action="{{ route('modules.leave.requests.hr.process', $leave->id) }}" class="hm-leave-process-form">
                @csrf
                <div class="hm-leave-process-form__field">
                    <label for="hr_comment" class="form-label">{{ __('employee_leave.fields.comment') }}</label>
                    <textarea
                        id="hr_comment"
                        name="comment"
                        class="form-control @error('comment') is-invalid @enderror"
                        rows="3"
                        maxlength="200"
                        placeholder="{{ __('employee_leave.processing.comment_placeholder') }}"
                    >{{ old('comment') }}</textarea>
                    @error('comment')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @error('decision')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="hm-leave-process-form__actions">
                    <button type="submit" name="decision" value="reject" class="btn hm-btn hm-btn--light hm-leave-process-form__btn--reject">
                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                        {{ __('employee_leave.processing.reject') }}
                    </button>
                    <button type="submit" name="decision" value="approve" class="btn hm-btn hm-btn--primary">
                        <i class="bi bi-check-circle" aria-hidden="true"></i>
                        {{ __('employee_leave.processing.approve') }}
                    </button>
                </div>
            </form>
        </div>
        @endcan
    @endif

    <div class="hm-leave-detail-card">
        <h2 class="hm-leave-detail-card__title">
            <span class="hm-leave-detail-card__icon hm-leave-detail-card__icon--history" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
            {{ __('employee_leave.sections.status_history') }}
        </h2>

        @if (count($history) > 0)
            <ul class="hm-history-list">
                @foreach ($history as $event)
                    <li class="hm-history-item">
                        <span class="hm-history-item__dot" aria-hidden="true"></span>
                        <div>
                            <div class="hm-history-item__stage">
                                {{ __('employee_leave.history.'.$event['stage']) }}
                            </div>
                            <div class="hm-history-item__status">{{ $event['status_label'] }}</div>
                            @if ($event['comment'] !== '')
                                <div class="hm-history-item__meta">{{ $event['comment'] }}</div>
                            @endif
                            <div class="hm-history-item__meta">{{ $event['at'] }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="hm-leave-detail-card__empty">{{ __('employee_leave.no_history') }}</p>
        @endif
    </div>
    </div>

    <div class="hm-leave-detail__footer">
        <a href="{{ route('modules.leave.requests.index') }}" class="btn hm-btn hm-btn--light">
            <i class="bi bi-list-ul" aria-hidden="true"></i>
            {{ __('employee_leave.view_requests') }}
        </a>
    </div>
    </div>
@endsection
