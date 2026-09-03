@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-doctors-directory-admin.css') }}?v={{ filemtime(public_path('css/hm-doctors-directory-admin.css')) }}" rel="stylesheet">
@endpush

@section('title', __('system_administration.packages'))

@section('sidebar_heading', __('system_administration.title'))
@section('sidebar_subheading', __('system_administration.packages_subtitle'))

@section('content')
    <div class="hm-hs hm-dda hm-dda--packages">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('system_administration.dashboard'), 'url' => route('modules.system-admin.dashboard')],
                ['label' => __('system_administration.packages'), 'chip' => true],
            ],
        ])

        <section class="hs-page-hero" aria-labelledby="sysAdminPackagesTitle">
            <div>
                <h1 id="sysAdminPackagesTitle">{{ __('system_administration.packages') }}</h1>
                <p>{{ __('system_administration.packages_subtitle') }}</p>
            </div>
            <div class="d-flex gap-2 align-items-start">
                <a class="hs-btn hs-btn--primary" href="{{ route('modules.system-admin.packages.create') }}"><i class="bi bi-plus-lg"></i> إضافة خدمة</a>
                <div class="hs-page-hero-art" aria-hidden="true"></div>
            </div>
        </section>

        @if (session('success'))
            <div class="hm-alert-success mb-3">{{ session('success') }}</div>
        @endif

        <div class="hs-filter-card mb-3">
            <div class="hs-filter-head"><span class="hs-filter-icon" aria-hidden="true"><i class="bi bi-file-earmark-spreadsheet"></i></span><h2>استيراد خدمات من Excel / CSV</h2></div>
            <form method="POST" action="{{ route('modules.system-admin.packages.import') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-end">
                @csrf
                <div class="hs-field"><label for="packageImportSection">القسم</label><select id="packageImportSection" name="service_id" required><option value="">اختر القسم</option>@foreach($sectionOptions as $sectionId => $sectionLabel)<option value="{{ $sectionId }}">{{ $sectionLabel }}</option>@endforeach</select></div>
                <div class="hs-field"><label for="packageImportFile">الملف</label><input id="packageImportFile" type="file" name="file" accept=".xlsx,.csv,.txt" required></div>
                <button class="hs-btn hs-btn--ghost" type="submit"><i class="bi bi-upload"></i> استيراد</button>
            </form>
        </div>

        <div class="hs-filter-card">
            <div class="hs-filter-head">
                <span class="hs-filter-icon" aria-hidden="true"><i class="bi bi-funnel"></i></span>
                <h2>{{ __('system_administration.filters_title') }}</h2>
            </div>

            <form method="GET" action="{{ route('modules.system-admin.packages.index') }}" class="hs-filter-grid dda-filter-grid--specialities">
                <div class="hs-field">
                    <label for="sysAdminPackageSearch">{{ __('system_administration.filters.search') }}</label>
                    <div class="hs-input-wrap">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input
                            type="search"
                            id="sysAdminPackageSearch"
                            name="search"
                            value="{{ $filters['search'] }}"
                            placeholder="{{ __('system_administration.filters.search') }}"
                            maxlength="100"
                        >
                    </div>
                </div>
                <div class="hs-field">
                    <label for="sysAdminPackageSection">{{ __('system_administration.filters.section') }}</label>
                    <select id="sysAdminPackageSection" name="section">
                        <option value="">{{ __('system_administration.filters.all_sections') }}</option>
                        @foreach ($sectionOptions as $sectionId => $sectionLabel)
                            <option value="{{ $sectionId }}" @selected((string) $filters['section'] === (string) $sectionId)>
                                {{ $sectionLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="hs-field">
                    <label for="sysAdminPackagePublish">{{ __('system_administration.filters.publish') }}</label>
                    <select id="sysAdminPackagePublish" name="publish">
                        <option value="">{{ __('system_administration.filters.all') }}</option>
                        <option value="1" @selected($filters['publish'] === '1')>{{ __('system_administration.status.published') }}</option>
                        <option value="0" @selected($filters['publish'] === '0')>{{ __('system_administration.status.unpublished') }}</option>
                    </select>
                </div>
                <button type="submit" class="hs-btn hs-btn--primary">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    {{ __('system_administration.search') }}
                </button>
                @if ($hasFilters)
                    <a href="{{ route('modules.system-admin.packages.index') }}" class="hs-btn hs-btn--ghost">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        {{ __('system_administration.reset') }}
                    </a>
                @endif
            </form>
        </div>

        @if ($packages->count() > 0)
            <div class="hs-list-panel">
                <div class="hm-doctors-admin-table-wrap">
                    <div class="hm-doctors-admin-table-scroll">
                        <table class="hm-doctors-admin-table">
                            <thead>
                                <tr>
                                    <th style="width:80px;">{{ __('system_administration.columns.id') }}</th>
                                    <th style="width:120px;">{{ __('system_administration.columns.code') }}</th>
                                    <th>{{ __('system_administration.columns.name_en') }}</th>
                                    <th>{{ __('system_administration.columns.name_ar') }}</th>
                                    <th>{{ __('system_administration.columns.section') }}</th>
                                    <th style="width:120px;">{{ __('system_administration.columns.price') }}</th>
                                    <th style="width:140px;">{{ __('system_administration.columns.status') }}</th>
                                    <th style="width:96px;" class="hm-actions-col">{{ __('system_administration.columns.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($packages as $package)
                                    @php
                                        $sectionLabel = $sectionOptions[(int) $package->service_id]
                                            ?? $package->section?->localizedName()
                                            ?? '—';
                                    @endphp
                                    <tr>
                                        <td class="hm-doctors-admin-cell--mono">#{{ $package->id }}</td>
                                        <td class="hm-doctors-admin-cell--mono hm-cell--wrap">{{ $package->code1 ?: '—' }}</td>
                                        <td class="hm-cell--wrap">{{ $package->name_en ?: '—' }}</td>
                                        <td class="hm-cell--wrap">{{ $package->name_ar ?: '—' }}</td>
                                        <td class="hm-cell--wrap">{{ $sectionLabel }}</td>
                                        <td class="hm-cell--nowrap">{{ $package->hasPrice() ? $package->formattedPriceWithCurrency() : '—' }}</td>
                                        <td class="hm-cell--nowrap">
                                            <span class="hm-doctors-admin-status hm-doctors-admin-status--{{ $package->publish === '1' ? 'published' : 'unpublished' }}">
                                                <i class="bi {{ $package->publish === '1' ? 'bi-check-circle' : 'bi-eye-slash' }}" aria-hidden="true"></i>
                                                {{ $package->publish === '1' ? __('system_administration.status.published') : __('system_administration.status.unpublished') }}
                                            </span>
                                        </td>
                                        <td class="hm-actions-col">
                                            <div class="dropdown hm-doctors-admin-actions-menu">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm hm-btn hm-btn--light dropdown-toggle hm-doctors-admin-actions-menu__toggle"
                                                    id="packageActions{{ $package->id }}"
                                                    data-bs-toggle="dropdown"
                                                    data-bs-auto-close="true"
                                                    aria-expanded="false"
                                                    aria-haspopup="true"
                                                    aria-label="{{ __('system_administration.columns.actions') }}"
                                                >
                                                    <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end hm-dropdown-menu" aria-labelledby="packageActions{{ $package->id }}">
                                                    <a class="hm-dropdown-menu__action" href="{{ route('modules.system-admin.packages.edit', $package->id) }}">
                                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                        {{ __('system_administration.edit') }}
                                                    </a>
                                                    <form method="POST" action="{{ route('modules.system-admin.packages.publish', $package->id) }}" class="m-0">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="hm-dropdown-menu__action">
                                                            <i class="bi {{ $package->publish === '1' ? 'bi-eye-slash' : 'bi-eye' }}" aria-hidden="true"></i>
                                                            {{ $package->publish === '1' ? __('system_administration.unpublish') : __('system_administration.publish') }}
                                                        </button>
                                                    </form>
                                                    <form
                                                        method="POST"
                                                        action="{{ route('modules.system-admin.packages.destroy', $package->id) }}"
                                                        class="m-0"
                                                        onsubmit="return confirm(@json(__('system_administration.confirm_delete')));"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="hm-dropdown-menu__action text-danger">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                            {{ __('system_administration.delete') }}
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
                    {{ $packages->links('pagination.hm') }}
                </div>
            </div>
        @else
            <div class="hs-empty">
                <i class="bi bi-hospital" aria-hidden="true"></i>
                <h2 class="hm-empty-state__title">{{ __('system_administration.empty_title') }}</h2>
                <p class="mb-0">{{ $hasFilters ? __('system_administration.no_results') : __('system_administration.no_packages') }}</p>
                @if ($hasFilters)
                    <a href="{{ route('modules.system-admin.packages.index') }}" class="hs-btn hs-btn--ghost mt-3">
                        {{ __('system_administration.reset') }}
                    </a>
                @endif
            </div>
        @endif
    </div>
@endsection
