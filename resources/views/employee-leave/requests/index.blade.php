@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-employee-leave.css') }}?v={{ filemtime(public_path('css/hm-employee-leave.css')) }}" rel="stylesheet">
@endpush

@section('title', __('employee_leave.requests'))

@section('sidebar_heading', __('employee_services.title'))
@section('sidebar_subheading', __('employee_leave.requests_subtitle'))

@section('content')
    <div class="hm-el hm-el--requests">
        @include('employee-leave.partials.el-breadcrumb', [
            'items' => [
                ['label' => __('employee_services.title'), 'url' => route('modules.employee-services')],
                ['label' => __('employee_leave.requests'), 'chip' => true],
            ],
        ])

        @include('employee-leave.partials.el-page-hero', [
            'title' => __('employee_leave.requests'),
            'subtitle' => __('employee_leave.requests_subtitle'),
        ])

        <div class="el-list-toolbar">
            <div class="el-list-toolbar__summary">
                {{ __('employee_leave.total_records', ['count' => number_format($requests->total())]) }}
            </div>
            <a href="{{ route('modules.leave.requests.create') }}" class="btn hm-btn hm-btn--primary">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                {{ __('employee_leave.apply_leave') }}
            </a>
        </div>

        <div class="el-filter-panel">
            <form method="GET" action="{{ route('modules.leave.requests.index') }}" class="hm-search-form hm-search-form--multi">
                <div class="hm-search-field">
                    <i class="bi bi-search hm-search-field__icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        name="search"
                        value="{{ $filters['search'] }}"
                        class="hm-search-field__input"
                        placeholder="{{ __('employee_leave.filters.search') }}"
                        maxlength="100"
                    >
                </div>
                <div class="hm-search-field" style="flex:0 1 180px;">
                    <select name="status" class="form-select hm-search-field__input" style="padding-inline-start:1rem;">
                        <option value="">{{ __('employee_leave.filters.status') }}</option>
                        @foreach (['pending', 'approved', 'rejected'] as $statusOption)
                            <option value="{{ $statusOption }}" @selected($filters['status'] === $statusOption)>
                                {{ __('employee_leave.status.'.$statusOption) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="hm-search-field" style="flex:0 1 200px;">
                    <select name="leave_type" class="form-select hm-search-field__input" style="padding-inline-start:1rem;">
                        <option value="">{{ __('employee_leave.filters.leave_type') }}</option>
                        @foreach ($leaveTypes as $typeId => $typeLabel)
                            <option value="{{ $typeId }}" @selected((string) $filters['leave_type'] === (string) $typeId)>
                                {{ $typeLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="hm-search-form__actions">
                    <button type="submit" class="btn hm-btn hm-btn--primary">{{ __('employee_leave.search') }}</button>
                    @if ($hasFilters)
                        <a href="{{ route('modules.leave.requests.index') }}" class="btn hm-btn hm-btn--outline">{{ __('employee_leave.reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        @if ($requests->count() > 0)
            <div class="el-table-panel">
                <div class="el-table-wrap">
                    <table class="hm-leave-table">
                    <thead>
                        <tr>
                            <th>{{ __('employee_leave.columns.request_no') }}</th>
                            <th>{{ __('employee_leave.columns.employee') }}</th>
                            <th>{{ __('employee_leave.columns.leave_type') }}</th>
                            <th>{{ __('employee_leave.columns.from_date') }}</th>
                            <th>{{ __('employee_leave.columns.to_date') }}</th>
                            <th>{{ __('employee_leave.columns.days') }}</th>
                            <th>{{ __('employee_leave.columns.status') }}</th>
                            <th>{{ __('employee_leave.columns.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $request)
                            <tr>
                                <td>#{{ $request->id }}</td>
                                <td>{{ $request->employeeDisplayName() }}</td>
                                <td>{{ $request->leaveTypeLabel() }}</td>
                                <td>{{ $request->formattedStartDate() }}</td>
                                <td>{{ $request->formattedEndDate() }}</td>
                                <td>{{ $request->days }}</td>
                                <td>
                                    <span class="hm-status-badge hm-status-badge--{{ $request->resolved_status }}">
                                        {{ __('employee_leave.status.'.$request->resolved_status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('modules.leave.requests.show', $request->id) }}" class="btn btn-sm hm-btn hm-btn--outline">
                                        {{ __('employee_leave.view') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>

                <div class="el-pagination">
                    {{ $requests->links('pagination.hm') }}
                </div>
            </div>
        @else
            <div class="hm-empty-state">
                <i class="bi bi-calendar-x" aria-hidden="true"></i>
                <p class="mb-0">{{ $hasFilters ? __('employee_leave.no_results') : __('employee_leave.no_requests') }}</p>
            </div>
        @endif
    </div>
@endsection
