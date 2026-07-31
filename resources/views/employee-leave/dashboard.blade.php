@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-employee-leave.css') }}?v={{ filemtime(public_path('css/hm-employee-leave.css')) }}" rel="stylesheet">
@endpush

@section('title', __('employee_leave.dashboard'))

@section('sidebar_heading', __('employee_services.title'))
@section('sidebar_subheading', __('employee_leave.dashboard_subtitle'))

@section('content')
    <div class="hm-el hm-el--dashboard">
        @include('employee-leave.partials.el-breadcrumb', [
            'items' => [
                ['label' => __('employee_services.title'), 'url' => route('modules.employee-services')],
                ['label' => __('employee_leave.dashboard'), 'chip' => true],
            ],
        ])

        @include('employee-leave.partials.el-page-hero', [
            'title' => __('employee_leave.dashboard'),
            'subtitle' => __('employee_leave.dashboard_subtitle'),
        ])

        <div class="hm-leave-stat-row">
            <div class="hm-card hm-stat-card d-flex h-100">
                <div class="hm-stat-card__icon hm-stat-card__icon--primary">
                    <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                </div>
                <div class="hm-stat-card__body">
                    <p class="hm-stat-card__label">{{ __('employee_leave.stats.total') }}</p>
                    <p class="hm-stat-card__value">{{ $summary['total'] }}</p>
                </div>
            </div>
            <div class="hm-card hm-stat-card d-flex h-100">
                <div class="hm-stat-card__icon hm-stat-card__icon--info">
                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                </div>
                <div class="hm-stat-card__body">
                    <p class="hm-stat-card__label">{{ __('employee_leave.stats.pending') }}</p>
                    <p class="hm-stat-card__value">{{ $summary['pending'] }}</p>
                </div>
            </div>
            <div class="hm-card hm-stat-card d-flex h-100">
                <div class="hm-stat-card__icon hm-stat-card__icon--success">
                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                </div>
                <div class="hm-stat-card__body">
                    <p class="hm-stat-card__label">{{ __('employee_leave.stats.approved') }}</p>
                    <p class="hm-stat-card__value">{{ $summary['approved'] }}</p>
                </div>
            </div>
            <div class="hm-card hm-stat-card d-flex h-100">
                <div class="hm-stat-card__icon hm-stat-card__icon--primary el-stat-icon--danger">
                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                </div>
                <div class="hm-stat-card__body">
                    <p class="hm-stat-card__label">{{ __('employee_leave.stats.rejected') }}</p>
                    <p class="hm-stat-card__value">{{ $summary['rejected'] }}</p>
                </div>
            </div>
        </div>

        <div class="hm-leave-actions">
            <a href="{{ route('modules.employee-services') }}" class="btn hm-btn hm-btn--outline">
                <i class="bi bi-grid" aria-hidden="true"></i>
                {{ __('employee_services.title') }}
            </a>
            <a href="{{ route('modules.leave.requests.index') }}" class="btn hm-btn hm-btn--outline">
                <i class="bi bi-list-ul" aria-hidden="true"></i>
                {{ __('employee_leave.view_requests') }}
            </a>
            <a href="{{ route('modules.leave.requests.create') }}" class="btn hm-btn hm-btn--primary">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                {{ __('employee_leave.apply_leave') }}
            </a>
        </div>
    </div>
@endsection
