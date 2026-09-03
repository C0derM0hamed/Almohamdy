@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-inquiries.css') }}?v={{ filemtime(public_path('css/hm-inquiries.css')) }}" rel="stylesheet">
@endpush

@section('title', $direction === 'incoming' ? __('inquiries.incoming_page_title') : __('inquiries.outgoing_page_title'))

@section('sidebar_heading', __('inquiries.title'))
@section('sidebar_subheading', __('inquiries.subtitle'))
@section('figma_page_header', true)

@section('content')
    @php
        $listRoute = $direction === 'incoming'
            ? route('modules.inquiries.incoming.index')
            : route('modules.inquiries.outgoing.index');
        $pageTitle = $direction === 'incoming'
            ? __('inquiries.incoming_page_title')
            : __('inquiries.outgoing_page_title');
        $crumbCurrent = $direction === 'incoming'
            ? __('inquiries.incoming')
            : __('inquiries.outgoing');
        $inquiryService = app(\App\Services\Inquiries\InquiryAndServiceService::class);
        $statIcons = [
            'new' => asset('images/figma/inquiries/stat-new.svg'),
            'in_progress' => asset('images/figma/inquiries/stat-review.svg'),
            'contacted' => asset('images/figma/inquiries/stat-phone.svg'),
            'contacted_not_booked' => asset('images/figma/inquiries/stat-bell-off.svg'),
            'completed' => asset('images/figma/inquiries/stat-check.svg'),
        ];
        $statVariants = ['primary', 'dark', 'primary', 'dark', 'primary'];
        $canCreateOutgoing = $direction === 'outgoing' && in_array((int) session('companies_groups_id'), [1, 3], true);
    @endphp

    <div class="hm-fm hm-inq hm-inq--list">
        @include('layouts.partials.figma-module-header', [
            'compact' => true,
            'title' => $pageTitle,
            'crumbs' => [
                ['label' => __('dashboard.modules')],
                ['label' => __('inquiries.services_group')],
                ['label' => $crumbCurrent],
            ],
        ])

        <div class="fm-hero fm-hero--split fm-inq-toolbar">
            <div class="fm-hero__copy">
                <h1 id="inqPageTitle">{{ $pageTitle }}</h1>
                @include('layouts.partials.figma.tabs', [
                    'ariaLabel' => __('inquiries.title'),
                    'tabs' => [
                        [
                            'label' => __('inquiries.nav.outgoing'),
                            'url' => route('modules.inquiries.outgoing.index', request()->except('page')),
                            'active' => $direction === 'outgoing',
                            'iconHtml' => '<img src="'.e(asset($direction === 'outgoing' ? 'images/figma/inquiries/tab-send-on.svg' : 'images/figma/inquiries/tab-send-off.svg')).'" alt="" width="18" height="18">',
                        ],
                        [
                            'label' => __('inquiries.nav.incoming'),
                            'url' => route('modules.inquiries.incoming.index', request()->except('page')),
                            'active' => $direction === 'incoming',
                            'iconHtml' => '<img src="'.e(asset($direction === 'incoming' ? 'images/figma/inquiries/tab-inbox-on.svg' : 'images/figma/inquiries/tab-inbox-off.svg')).'" alt="" width="18" height="18">',
                        ],
                    ],
                ])
            </div>
            @if ($canCreateOutgoing)
                <a href="{{ route('modules.inquiries.outgoing.create') }}" class="fm-btn--cta">
                    <img src="{{ asset('images/figma/inquiries/plus.svg') }}" alt="" width="18" height="18">
                    {{ __('inquiries.new_inquiry') }}
                </a>
            @endif
        </div>

        <div class="fm-stats">
            @php $statIndex = 0; @endphp
            @foreach (config('hm.inquiries.stat_statuses', []) as $statKey => $statusIds)
                @php
                    $statQuery = array_merge(
                        request()->except(['page', 'stat', 'status']),
                        ['stat' => $statKey]
                    );
                    $isActive = ($filters['stat'] ?? '') === $statKey;
                @endphp
                @include('layouts.partials.figma.stat-status', [
                    'label' => __('inquiries.stats.'.$statKey),
                    'value' => number_format($statusCounts[$statKey] ?? 0),
                    'url' => $listRoute.'?'.http_build_query($statQuery),
                    'variant' => $statVariants[$statIndex] ?? 'primary',
                    'isActive' => $isActive,
                    'iconHtml' => '<img src="'.e($statIcons[$statKey]).'" alt="" width="20" height="20">',
                ])
                @php $statIndex++; @endphp
            @endforeach
        </div>

        <section class="fm-search" aria-labelledby="inqFiltersTitle">
            <div class="fm-search__head">
                <h2 id="inqFiltersTitle">{{ __('inquiries.filters_title') }}</h2>
            </div>
            <form method="GET" action="{{ $listRoute }}" class="fm-search__row">
                @if (($filters['stat'] ?? '') !== '')
                    <input type="hidden" name="stat" value="{{ $filters['stat'] }}">
                @endif

                <div class="fm-field">
                    <label for="inqDateFrom">{{ __('inquiries.filters.date_from') }}</label>
                    <input class="fm-input" type="date" id="inqDateFrom" name="date_from" value="{{ $filters['date_from'] }}">
                </div>
                <div class="fm-field">
                    <label for="inqDateTo">{{ __('inquiries.filters.date_to') }}</label>
                    <input class="fm-input" type="date" id="inqDateTo" name="date_to" value="{{ $filters['date_to'] }}">
                </div>
                <div class="fm-field">
                    <label for="inqStatus">{{ __('inquiries.filters.status') }}</label>
                    <select class="fm-input" id="inqStatus" name="status">
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
                <div class="fm-field">
                    <label for="inqDepartment">{{ __('inquiries.filters.department') }}</label>
                    <select class="fm-input" id="inqDepartment" name="department">
                        <option value="">{{ __('inquiries.filters.department_all') }}</option>
                        @foreach ($departmentOptions as $department)
                            <option value="{{ $department->id }}" @selected((string) $filters['department'] === (string) $department->id)>
                                {{ $department->legacyNavName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="fm-field">
                    <label for="inqMobile">{{ __('inquiries.filters.mobile') }}</label>
                    <input class="fm-input" type="text" id="inqMobile" name="mobile" value="{{ $filters['mobile'] }}" maxlength="20">
                </div>
                <button type="submit" class="fm-btn--search">{{ __('inquiries.search') }}</button>
                @if ($hasFilters)
                    <a href="{{ $listRoute }}" class="fm-btn--reset">
                        {{ __('inquiries.reset') }}
                        <img src="{{ asset('images/figma/doctors/reset.svg') }}" alt="" width="18" height="18">
                    </a>
                @endif
            </form>
        </section>

        <section class="fm-section">
            @include('layouts.partials.figma.section-head', [
                'title' => $crumbCurrent,
                'countLabel' => __('inquiries.total_records', ['count' => number_format($inquiries->total())]),
                'iconHtml' => '<img src="'.e(asset('images/figma/inquiries/tab-send-off.svg')).'" alt="" width="18" height="18">',
            ])

            <div class="fm-table-wrap">
                @if ($inquiries->count() > 0)
                    <div class="fm-table-scroll">
                        <table class="fm-table">
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
                                    <th scope="col">{{ __('inquiries.columns.timeline') }}</th>
                                    <th scope="col">{{ __('inquiries.columns.form') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($inquiries as $item)
                                    @php
                                        $itemStatusLabel = $inquiryService->statusLabel($item);
                                        $itemStatusColor = $inquiryService->statusColor($item);
                                        $canUpdateStatus = $inquiryService->canUpdateStatus($item);
                                        $enquirerName = $item->enquirerDisplayName();
                                        $nameParts = preg_split('/\s+/u', trim($enquirerName)) ?: [];
                                        $initials = $enquirerName === '—'
                                            ? '؟'
                                            : mb_substr($nameParts[0] ?? '', 0, 1).mb_substr($nameParts[1] ?? '', 0, 1);
                                        $timestamp = (int) $item->date;
                                        $dateObj = $timestamp > 0
                                            ? \Carbon\Carbon::createFromTimestamp($timestamp)->locale(app()->getLocale())
                                            : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fm-date">
                                                <span class="fm-date__day">{{ $dateObj?->translatedFormat('j F Y') ?? '—' }}</span>
                                                @if ($dateObj)
                                                    <span class="fm-date__time">{{ $dateObj->format('H:i:s') }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            <a
                                                href="{{ route('modules.inquiries.show', ['direction' => $direction, 'inquiry' => $item->id]) }}"
                                                class="fm-person"
                                            >
                                                <span class="fm-avatar">{{ $initials }}</span>
                                                <span>{{ $enquirerName }}</span>
                                            </a>
                                        </td>
                                        <td>{{ $item->mobile ?: '—' }}</td>
                                        <td>
                                            @include('layouts.partials.figma.badge', [
                                                'label' => $item->inquiredSection?->legacyNavName() ?? '—',
                                            ])
                                        </td>
                                        <td>
                                            @include('layouts.partials.figma.badge', [
                                                'label' => $item->senderBranch?->localizedName() ?? '—',
                                                'tone' => 'muted',
                                            ])
                                        </td>
                                        @if ($direction === 'incoming')
                                            <td>
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
                                                            data-inq-status-subtitle="#{{ $item->id }} — {{ $enquirerName }}"
                                                            title="{{ __('inquiries.add_status') }}"
                                                            aria-label="{{ __('inquiries.add_status') }}"
                                                        >
                                                            <img src="{{ asset('images/figma/inquiries/stat-new.svg') }}" alt="" width="14" height="14">
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                        <td>
                                            <a
                                                href="{{ route('modules.inquiries.timeline', ['direction' => $direction, 'inquiry' => $item->id]) }}"
                                                class="fm-badge"
                                                data-inq-timeline-modal
                                                data-no-transition="true"
                                                data-inq-timeline-url="{{ route('modules.inquiries.timeline', ['direction' => $direction, 'inquiry' => $item->id]) }}"
                                                data-inq-timeline-subtitle="#{{ $item->id }} — {{ $enquirerName }}"
                                                title="{{ __('inquiries.timeline') }}"
                                                aria-label="{{ __('inquiries.timeline') }}"
                                            >
                                                <img src="{{ asset('images/figma/system/pkg-clock.svg') }}" alt="" width="15" height="15">
                                            </a>
                                        </td>
                                        <td>
                                            <a
                                                href="{{ route('modules.inquiries.pdf', ['direction' => $direction, 'inquiry' => $item->id]) }}"
                                                class="fm-badge"
                                                download
                                                data-no-transition="true"
                                                title="{{ __('inquiries.view_pdf') }}"
                                                aria-label="{{ __('inquiries.view_pdf') }}"
                                            >
                                                <img src="{{ asset('images/figma/inquiries/file.svg') }}" alt="" width="15" height="15">
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @include('layouts.partials.figma.pagination', [
                        'paginator' => $inquiries,
                    ])
                @else
                    <div class="fm-empty">
                        <p class="mb-0">{{ $hasFilters ? __('inquiries.no_results') : __('inquiries.no_inquiries') }}</p>
                    </div>
                @endif
            </div>
        </section>
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
