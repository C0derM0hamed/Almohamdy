@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-components.css') }}?v={{ $hmAssetVersion ?? filemtime(public_path('css/hm-components.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inquiries.css') }}?v={{ filemtime(public_path('css/hm-inquiries.css')) }}" rel="stylesheet">
@endpush

@section('title', $direction === 'incoming' ? __('inquiries.incoming_page_title') : __('inquiries.outgoing_page_title'))

@section('sidebar_heading', __('inquiries.title'))
@section('sidebar_subheading', __('inquiries.subtitle'))

@section('content')
    @php
        $listRoute = $direction === 'incoming'
            ? route('modules.inquiries.incoming.index')
            : route('modules.inquiries.outgoing.index');
        $pageTitle = $direction === 'incoming'
            ? __('inquiries.incoming_page_title')
            : __('inquiries.outgoing_page_title');
        $inquiryService = app(\App\Services\Inquiries\InquiryAndServiceService::class);
    @endphp

    <div class="hm-inq hm-inq--list">
        @include('inquiries.partials.inq-breadcrumb', [
            'items' => [
                ['label' => $pageTitle, 'chip' => true],
            ],
        ])

        <section class="inq-page-hero" aria-labelledby="inqPageTitle">
            <div>
                <div class="inq-nav-tabs">
                    <a
                        href="{{ route('modules.inquiries.outgoing.index', request()->except('page')) }}"
                        class="inq-nav-tabs__item {{ $direction === 'outgoing' ? 'is-active' : '' }}"
                    >
                        {{ __('inquiries.nav.outgoing') }}
                    </a>
                    <a
                        href="{{ route('modules.inquiries.incoming.index', request()->except('page')) }}"
                        class="inq-nav-tabs__item {{ $direction === 'incoming' ? 'is-active' : '' }}"
                    >
                        {{ __('inquiries.nav.incoming') }}
                    </a>
                </div>
                <h1 id="inqPageTitle">{{ $pageTitle }}</h1>
            </div>
            <div class="d-flex align-items-center gap-3">
                @if ($direction === 'outgoing' && in_array((int) session('companies_groups_id'), [1, 3], true))
                    <a href="{{ route('modules.inquiries.outgoing.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        {{ __('inquiries.new_inquiry') }}
                    </a>
                @endif
                <div class="inq-page-hero-art" aria-hidden="true"></div>
            </div>
        </section>

        <div class="inq-stat-grid">
            @foreach (config('hm.inquiries.stat_statuses', []) as $statKey => $statusIds)
                @php
                    $statQuery = array_merge(
                        request()->except(['page', 'stat', 'status']),
                        ['stat' => $statKey]
                    );
                    $isActive = ($filters['stat'] ?? '') === $statKey;
                @endphp
                <a
                    href="{{ $listRoute.'?'.http_build_query($statQuery) }}"
                    class="inq-stat-card {{ $isActive ? 'is-active' : '' }}"
                >
                    <span class="inq-stat-card__label">{{ __('inquiries.stats.'.$statKey) }}</span>
                    <span class="inq-stat-card__value">{{ number_format($statusCounts[$statKey] ?? 0) }}</span>
                </a>
            @endforeach
        </div>

        <div class="inq-filter-panel">
            <form method="GET" action="{{ $listRoute }}" class="hm-clinician-filter-form">
                @if (($filters['stat'] ?? '') !== '')
                    <input type="hidden" name="stat" value="{{ $filters['stat'] }}">
                @endif

                <div class="hm-clinician-filter-form__grid inq-filter-grid">
                    <div class="hm-clinician-filter-field">
                        <label class="hm-clinician-filter-field__label" for="inqDateFrom">{{ __('inquiries.filters.date_from') }}</label>
                        <input
                            type="date"
                            id="inqDateFrom"
                            name="date_from"
                            value="{{ $filters['date_from'] }}"
                            class="hm-clinician-filter-field__input"
                        >
                    </div>

                    <div class="hm-clinician-filter-field">
                        <label class="hm-clinician-filter-field__label" for="inqDateTo">{{ __('inquiries.filters.date_to') }}</label>
                        <input
                            type="date"
                            id="inqDateTo"
                            name="date_to"
                            value="{{ $filters['date_to'] }}"
                            class="hm-clinician-filter-field__input"
                        >
                    </div>

                    <div class="hm-clinician-filter-field">
                        <label class="hm-clinician-filter-field__label" for="inqStatus">{{ __('inquiries.filters.status') }}</label>
                        <select id="inqStatus" name="status" class="hm-clinician-filter-field__input">
                            <option value="">{{ __('inquiries.filters.status_all') }}</option>
                            <option value="999999" @selected((string) $filters['status'] === '999999')>
                                {{ __('inquiries.status.new') }}
                            </option>
                            @foreach ($statusOptions as $statusOption)
                                <option value="{{ $statusOption->id }}" @selected((string) $filters['status'] === (string) $statusOption->id)>
                                    {{ $statusOption->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="hm-clinician-filter-field">
                        <label class="hm-clinician-filter-field__label" for="inqDepartment">{{ __('inquiries.filters.department') }}</label>
                        <select id="inqDepartment" name="department" class="hm-clinician-filter-field__input">
                            <option value="">{{ __('inquiries.filters.department_all') }}</option>
                            @foreach ($departmentOptions as $department)
                                <option value="{{ $department->id }}" @selected((string) $filters['department'] === (string) $department->id)>
                                    {{ $department->legacyNavName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="hm-clinician-filter-field">
                        <label class="hm-clinician-filter-field__label" for="inqMobile">{{ __('inquiries.filters.mobile') }}</label>
                        <input
                            type="text"
                            id="inqMobile"
                            name="mobile"
                            value="{{ $filters['mobile'] }}"
                            class="hm-clinician-filter-field__input"
                            maxlength="20"
                        >
                    </div>
                </div>

                <div class="hm-clinician-filter-form__actions">
                    <button type="submit" class="btn hm-btn hm-btn--primary hm-inq-btn">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        {{ __('inquiries.search') }}
                    </button>
                    @if ($hasFilters)
                        <a href="{{ $listRoute }}" class="btn hm-btn hm-btn--outline hm-inq-btn">
                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                            {{ __('inquiries.reset') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="inq-list-toolbar">
            <div class="inq-list-toolbar__summary">
                {{ __('inquiries.total_records', ['count' => number_format($inquiries->total())]) }}
            </div>
        </div>

        <div class="inq-table-panel">
            @if ($inquiries->count() > 0)
                <div class="hm-inq-table-wrap">
                    <div class="hm-inq-table-scroll">
                        <table class="hm-inq-table hm-inq-table--list">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('inquiries.columns.date') }}</th>
                                    <th scope="col">{{ __('inquiries.columns.enquirer') }}</th>
                                    <th scope="col">{{ __('inquiries.columns.mobile') }}</th>
                                    <th scope="col">{{ __('inquiries.columns.department') }}</th>
                                    <th scope="col">{{ __('inquiries.columns.sender_section') }}</th>
                                    @if ($direction === 'incoming')
                                        <th scope="col">{{ __('inquiries.columns.status') }}</th>
                                    @endif
                                    <th scope="col" class="hm-inq-table__col-action">{{ __('inquiries.columns.timeline') }}</th>
                                    <th scope="col" class="hm-inq-table__col-action">{{ __('inquiries.columns.form') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($inquiries as $item)
                                    @php
                                        $itemStatusLabel = $inquiryService->statusLabel($item);
                                        $itemStatusColor = $inquiryService->statusColor($item);
                                        $canUpdateStatus = $inquiryService->canUpdateStatus($item);
                                        $dateParts = explode(' ', $item->formattedDate(), 2);
                                    @endphp
                                    <tr>
                                        <td class="hm-inq-table__cell hm-inq-table__cell--date">
                                            <span class="hm-inq-date">
                                                <span class="hm-inq-date__day">{{ $dateParts[0] ?? '—' }}</span>
                                                @if (! empty($dateParts[1]))
                                                    <span class="hm-inq-date__time">{{ $dateParts[1] }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="hm-inq-table__cell hm-inq-table__cell--name">
                                            <a href="{{ route('modules.inquiries.show', ['direction' => $direction, 'inquiry' => $item->id]) }}" class="hm-inq-patient">
                                                {{ $item->enquirerDisplayName() }}
                                            </a>
                                        </td>
                                        <td class="hm-inq-table__cell hm-inq-table__cell--mobile">
                                            <span class="hm-inq-mobile">{{ $item->mobile ?: '—' }}</span>
                                        </td>
                                        <td class="hm-inq-table__cell hm-inq-table__cell--dept">
                                            {{ $item->inquiredSection?->legacyNavName() ?? '—' }}
                                        </td>
                                        <td class="hm-inq-table__cell hm-inq-table__cell--dept">
                                            {{ $item->senderBranch?->localizedName() ?? '—' }}
                                        </td>
                                        @if ($direction === 'incoming')
                                            <td class="hm-inq-table__cell hm-inq-table__cell--status">
                                                <div class="inq-status-cell">
                                                    <span class="inq-status-pill" style="--inq-status-color: {{ $itemStatusColor }}">
                                                        {{ $itemStatusLabel }}
                                                    </span>
                                                    @if ($canUpdateStatus)
                                                        <button
                                                            type="button"
                                                            class="inq-icon-btn inq-icon-btn--plus"
                                                            data-inq-status-modal
                                                            data-no-transition="true"
                                                            data-inq-status-url="{{ route('modules.inquiries.status', ['direction' => $direction, 'inquiry' => $item->id]) }}"
                                                            data-inq-status-subtitle="#{{ $item->id }} — {{ $item->enquirerDisplayName() }}"
                                                            title="{{ __('inquiries.add_status') }}"
                                                            aria-label="{{ __('inquiries.add_status') }}"
                                                        >
                                                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                        <td class="hm-inq-table__col-action">
                                            <a
                                                href="{{ route('modules.inquiries.timeline', ['direction' => $direction, 'inquiry' => $item->id]) }}"
                                                class="inq-icon-btn"
                                                data-inq-timeline-modal
                                                data-no-transition="true"
                                                data-inq-timeline-url="{{ route('modules.inquiries.timeline', ['direction' => $direction, 'inquiry' => $item->id]) }}"
                                                data-inq-timeline-subtitle="#{{ $item->id }} — {{ $item->enquirerDisplayName() }}"
                                                title="{{ __('inquiries.timeline') }}"
                                                aria-label="{{ __('inquiries.timeline') }}"
                                            >
                                                <i class="bi bi-clock-history" aria-hidden="true"></i>
                                            </a>
                                        </td>
                                        <td class="hm-inq-table__col-action">
                                            <a
                                                href="{{ route('modules.inquiries.pdf', ['direction' => $direction, 'inquiry' => $item->id]) }}"
                                                class="inq-pdf-link"
                                                download
                                                data-no-transition="true"
                                                title="{{ __('inquiries.view_pdf') }}"
                                                aria-label="{{ __('inquiries.view_pdf') }}"
                                            >
                                                <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-center inq-pagination">
                    {{ $inquiries->links('pagination.hm') }}
                </div>
            @else
                <div class="hm-empty-state hm-empty-state--in-card">
                    <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                    <p class="mb-0">{{ $hasFilters ? __('inquiries.no_results') : __('inquiries.no_inquiries') }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade inq-timeline-modal" id="inqTimelineModal" tabindex="-1" aria-labelledby="inqTimelineModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5 mb-0" id="inqTimelineModalLabel">{{ __('inquiries.timeline') }}</h2>
                        <p class="text-muted small mb-0 mt-1" id="inqTimelineModalSubtitle"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('inquiries.close') }}"></button>
                </div>
                <div
                    class="modal-body"
                    id="inqTimelineModalBody"
                    data-loading-label="{{ __('inquiries.timeline_loading') }}"
                    data-error-label="{{ __('inquiries.timeline_load_error') }}"
                >
                    <div class="inq-timeline-modal-state" role="status">
                        <span>{{ __('inquiries.timeline_subtitle') }}</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('inquiries.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div
        class="modal fade inq-status-modal"
        id="inqStatusModal"
        tabindex="-1"
        aria-labelledby="inqStatusModalLabel"
        aria-hidden="true"
        data-forward-status-id="{{ $forwardStatusId }}"
        data-label-error="{{ __('inquiries.status_form.error') }}"
        data-label-department-required="{{ __('inquiries.status_form.department_required') }}"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5 mb-0" id="inqStatusModalLabel">{{ __('inquiries.status_form.title') }}</h2>
                        <p class="text-muted small mb-0 mt-1" id="inqStatusModalSubtitle">{{ __('inquiries.status_form.subtitle') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('inquiries.close') }}"></button>
                </div>
                <form method="POST" action="#" id="inqStatusForm" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div id="inqStatusFormError" class="alert alert-danger d-none" role="alert"></div>

                        <div class="inq-status-form-grid">
                            <div class="inq-status-field">
                                <label for="inq_sender">{{ __('inquiries.status_form.sender') }}</label>
                                <input
                                    type="text"
                                    id="inq_sender"
                                    class="form-control"
                                    value="{{ $senderName }}"
                                    readonly
                                >
                            </div>

                            <div class="inq-status-field">
                                <label for="inq_status_id">{{ __('inquiries.status_form.status') }}</label>
                                <select id="inq_status_id" name="status_id" class="form-select" required>
                                    <option value="">{{ __('inquiries.status_form.status_placeholder') }}</option>
                                    @foreach ($updateStatusOptions as $option)
                                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="inq-status-field d-none" id="inqStatusDepartmentWrap">
                                <label for="inq_department_id">{{ __('inquiries.status_form.department') }}</label>
                                <select id="inq_department_id" name="department_id" class="form-select">
                                    <option value="">{{ __('inquiries.status_form.department_placeholder') }}</option>
                                    @foreach ($departmentOptions as $department)
                                        <option value="{{ $department->id }}">{{ $department->localizedName() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <input type="hidden" name="assignment_type" value="department">

                            <div class="inq-status-field inq-status-field--full">
                                <label for="inq_notes">{{ __('inquiries.status_form.notes') }}</label>
                                <textarea
                                    id="inq_notes"
                                    name="notes"
                                    class="form-control"
                                    rows="3"
                                    maxlength="1000"
                                    placeholder="{{ __('inquiries.status_form.notes_placeholder') }}"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('inquiries.status_form.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="inqStatusSubmitBtn">{{ __('inquiries.status_form.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/hm-inquiry-timeline-modal.js') }}?v={{ filemtime(public_path('js/hm-inquiry-timeline-modal.js')) }}" defer></script>
    <script src="{{ asset('js/hm-inquiry-status-modal.js') }}?v={{ filemtime(public_path('js/hm-inquiry-status-modal.js')) }}" defer></script>
@endpush
