@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-doctors-directory-admin.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory-admin.css')) }}" rel="stylesheet">
@endpush

@section('title', __('doctors_directory_admin.specialities'))

@section('sidebar_heading', __('doctors_directory_admin.title'))
@section('sidebar_subheading', __('doctors_directory_admin.specialities_subtitle'))

@section('content')
    <div class="hm-hs hm-dda hm-dda--specialities">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('doctors_directory_admin.dashboard'), 'url' => route('modules.doctors-admin.dashboard')],
                ['label' => __('doctors_directory_admin.specialities'), 'chip' => true],
            ],
        ])

        <section class="hs-page-hero" aria-labelledby="ddaSpecialitiesTitle">
            <div>
                <h1 id="ddaSpecialitiesTitle">{{ __('doctors_directory_admin.specialities') }}</h1>
                <p>{{ __('doctors_directory_admin.specialities_subtitle') }}</p>
            </div>
            <div class="hs-page-hero-art" aria-hidden="true"></div>
        </section>

        @if (session('success'))
            <div class="hm-alert-success mb-3">{{ session('success') }}</div>
        @endif

        <div class="dda-action-bar">
            <a href="{{ route('modules.doctors-admin.specialities.create') }}" class="hs-btn hs-btn--primary text-decoration-none">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                {{ __('doctors_directory_admin.add_speciality') }}
            </a>
        </div>

        <div class="hs-filter-card">
            <div class="hs-filter-head">
                <span class="hs-filter-icon" aria-hidden="true"><i class="bi bi-funnel"></i></span>
                <h2>{{ __('doctors_directory_admin.filters_title') }}</h2>
            </div>

            <form method="GET" action="{{ route('modules.doctors-admin.specialities.index') }}" class="hs-filter-grid dda-filter-grid--specialities">
                <div class="hs-field">
                    <label for="ddaSpecialitySearch">{{ __('doctors_directory_admin.filters.search') }}</label>
                    <div class="hs-input-wrap">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input
                            type="search"
                            id="ddaSpecialitySearch"
                            name="search"
                            value="{{ $filters['search'] }}"
                            placeholder="{{ __('doctors_directory_admin.filters.search') }}"
                            maxlength="100"
                        >
                    </div>
                </div>
                <div class="hs-field">
                    <label for="ddaSpecialityPublish">{{ __('doctors_directory_admin.filters.publish') }}</label>
                    <select id="ddaSpecialityPublish" name="publish">
                        <option value="">{{ __('doctors_directory_admin.filters.all') }}</option>
                        <option value="1" @selected($filters['publish'] === '1')>{{ __('doctors_directory_admin.status.published') }}</option>
                        <option value="0" @selected($filters['publish'] === '0')>{{ __('doctors_directory_admin.status.unpublished') }}</option>
                    </select>
                </div>
                <button type="submit" class="hs-btn hs-btn--primary">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    {{ __('doctors_directory_admin.search') }}
                </button>
                @if ($hasFilters)
                    <a href="{{ route('modules.doctors-admin.specialities.index') }}" class="hs-btn hs-btn--ghost">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        {{ __('doctors_directory_admin.reset') }}
                    </a>
                @endif
            </form>
        </div>

        @if ($specialities->count() > 0)
            <div class="hs-list-panel">
                <div class="hm-doctors-admin-table-wrap">
                    <div class="hm-doctors-admin-table-scroll">
                        <table class="hm-doctors-admin-table">
                            <thead>
                                <tr>
                                    <th style="width:80px;">{{ __('doctors_directory_admin.columns.id') }}</th>
                                    <th>{{ __('doctors_directory_admin.columns.name_en') }}</th>
                                    <th>{{ __('doctors_directory_admin.columns.name_ar') }}</th>
                                    <th>{{ __('doctors_directory_admin.columns.clinic') }}</th>
                                    <th style="width:110px;">{{ __('doctors_directory_admin.columns.departments') }}</th>
                                    <th style="width:90px;">{{ __('doctors_directory_admin.columns.doctors') }}</th>
                                    <th style="width:140px;">{{ __('doctors_directory_admin.columns.status') }}</th>
                                    <th style="width:96px;" class="hm-actions-col">{{ __('doctors_directory_admin.columns.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($specialities as $speciality)
                                    <tr>
                                        <td class="hm-doctors-admin-cell--mono">#{{ $speciality->id }}</td>
                                        <td>{{ $speciality->subject_en ?: '—' }}</td>
                                        <td>{{ $speciality->subject_ar ?: '—' }}</td>
                                        <td>{{ $speciality->clinic?->localizedName() ?? '—' }}</td>
                                        <td>{{ $speciality->departments_count }}</td>
                                        <td>{{ $speciality->doctors_count }}</td>
                                        <td>
                                            <span class="hm-doctors-admin-status hm-doctors-admin-status--{{ $speciality->publish === '1' ? 'published' : 'unpublished' }}">
                                                <i class="bi {{ $speciality->publish === '1' ? 'bi-check-circle' : 'bi-eye-slash' }}" aria-hidden="true"></i>
                                                {{ $speciality->publish === '1' ? __('doctors_directory_admin.status.published') : __('doctors_directory_admin.status.unpublished') }}
                                            </span>
                                        </td>
                                        <td class="hm-actions-col">
                                            <div class="dropdown hm-doctors-admin-actions-menu">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm hm-btn hm-btn--light dropdown-toggle hm-doctors-admin-actions-menu__toggle"
                                                    id="specialityActions{{ $speciality->id }}"
                                                    data-bs-toggle="dropdown"
                                                    data-bs-auto-close="true"
                                                    aria-expanded="false"
                                                    aria-haspopup="true"
                                                    aria-label="{{ __('doctors_directory_admin.columns.actions') }}"
                                                >
                                                    <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end hm-dropdown-menu" aria-labelledby="specialityActions{{ $speciality->id }}">
                                                    <a class="hm-dropdown-menu__action" href="{{ route('modules.doctors-admin.specialities.edit', $speciality->id) }}">
                                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                        {{ __('doctors_directory_admin.edit') }}
                                                    </a>
                                                    <a
                                                        class="hm-dropdown-menu__action"
                                                        href="{{ route('modules.doctors-admin.departments.index', ['speciality' => $speciality->id]) }}"
                                                    >
                                                        <i class="bi bi-building" aria-hidden="true"></i>
                                                        {{ __('doctors_directory_admin.departments') }}
                                                    </a>
                                                    <form method="POST" action="{{ route('modules.doctors-admin.specialities.publish', $speciality->id) }}" class="m-0">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="hm-dropdown-menu__action">
                                                            <i class="bi {{ $speciality->publish === '1' ? 'bi-eye-slash' : 'bi-eye' }}" aria-hidden="true"></i>
                                                            {{ $speciality->publish === '1' ? __('doctors_directory_admin.unpublish') : __('doctors_directory_admin.publish') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $specialities->links('pagination.hm') }}
                </div>
            </div>
        @else
            <div class="hs-empty">
                <i class="bi bi-diagram-3" aria-hidden="true"></i>
                <h2 class="hm-empty-state__title">{{ __('doctors_directory_admin.empty_title') }}</h2>
                <p class="mb-0">{{ $hasFilters ? __('doctors_directory_admin.no_results') : __('doctors_directory_admin.no_specialities') }}</p>
                @if ($hasFilters)
                    <a href="{{ route('modules.doctors-admin.specialities.index') }}" class="hs-btn hs-btn--ghost mt-3">
                        {{ __('doctors_directory_admin.reset') }}
                    </a>
                @else
                    <a href="{{ route('modules.doctors-admin.specialities.create') }}" class="hs-btn hs-btn--primary mt-3">
                        {{ __('doctors_directory_admin.add_speciality') }}
                    </a>
                @endif
            </div>
        @endif
    </div>
@endsection
