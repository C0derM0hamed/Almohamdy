@extends('layouts.app')
@section('title', __('gov_accounts.hr.title'))
@section('sidebar_heading', __('gov_accounts.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')
@php
    $nameOf = static function ($item) {
        if (! $item) return '—';
        if (method_exists($item, 'displayName')) return $item->displayName();
        if (method_exists($item, 'localizedName')) return $item->localizedName();
        $field = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        return data_get($item, $field) ?: data_get($item, 'name') ?: '—';
    };
@endphp
<div class="hm-licenses">
    @include('licenses.partials.page-header', ['title' => __('gov_accounts.hr.title'), 'subtitle' => __('gov_accounts.hr.subtitle'), 'icon' => 'bi-people'])
    @include('licenses.partials.feedback')

    <section class="lic-toolbar" aria-labelledby="govHrSearchTitle">
        <h2 id="govHrSearchTitle" class="lic-toolbar__title"><i class="bi bi-search"></i>{{ __('gov_accounts.actions.search') }}</h2>
        <form method="GET" action="{{ route('modules.gov-accounts.hr.index') }}">
            <div class="lic-filter-grid">
                <div class="lic-field lic-field--span-2">
                    <label for="gov_hr_search">{{ __('gov_accounts.hr.search') }}</label>
                    <input id="gov_hr_search" class="form-control" type="search" name="search" value="{{ $search }}" placeholder="{{ __('gov_accounts.hr.search') }}">
                </div>
                <div class="lic-filter-actions">
                    <button class="lic-btn lic-btn--primary" type="submit"><i class="bi bi-search"></i>{{ __('gov_accounts.actions.search') }}</button>
                    <a class="lic-btn" href="{{ route('modules.gov-accounts.hr.index') }}">{{ __('gov_accounts.actions.cancel') }}</a>
                </div>
            </div>
        </form>
    </section>

    <section class="lic-panel">
        <div class="lic-panel__head">
            <h2 class="lic-panel__title"><i class="bi bi-person-vcard"></i>{{ __('gov_accounts.accounts.title') }}</h2>
            <span>{{ $accounts->total() }}</span>
        </div>
        <div class="lic-table-wrap">
            <table class="lic-table">
                <thead>
                    <tr>
                        <th>{{ __('gov_accounts.fields.employee') }}</th>
                        <th>{{ __('gov_accounts.fields.username') }}</th>
                        <th>{{ __('gov_accounts.fields.department_unit') }}</th>
                        <th>{{ __('gov_accounts.fields.authority') }}</th>
                        <th>{{ __('gov_accounts.fields.service') }}</th>
                        <th>{{ __('gov_accounts.fields.status') }}</th>
                        <th>{{ __('gov_accounts.actions.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($accounts as $account)
                    @php
                        $canAct = ! in_array($account->status, ['modification_requested', 'suspension_requested', 'closure_requested', 'closed'], true);
                        $previewDetails = [
                            'username' => $account->username ?: '—',
                            'employee' => $nameOf($account->employee),
                            'department' => $account->sourceRequest?->department?->hierarchyLabel() ?: '—',
                            'authority' => $nameOf($account->authority),
                            'service' => $nameOf($account->service),
                            'role' => $nameOf($account->role),
                            'branch' => $nameOf($account->hospitalBranch),
                            'status' => __('gov_accounts.account_statuses.'.$account->status),
                            'status_key' => $account->status,
                            'url' => route('modules.gov-accounts.accounts.show', $account),
                            'action' => route('modules.gov-accounts.hr.requests.store', $account),
                        ];
                    @endphp
                    <tr>
                        <td>
                            @if($canAct)
                                <button type="button" class="lic-table__primary lic-table__primary--button" data-bs-toggle="modal" data-bs-target="#govHrLifecycleModal" data-license-preview='@json($previewDetails)'>{{ $nameOf($account->employee) }}</button>
                            @else
                                <a class="lic-table__primary" href="{{ route('modules.gov-accounts.accounts.show', $account) }}">{{ $nameOf($account->employee) }}</a>
                            @endif
                            <span class="lic-table__sub">{{ $nameOf($account->hospitalBranch) }}</span>
                        </td>
                        <td class="lic-sensitive">{{ $account->username }}</td>
                        <td>{{ $account->sourceRequest?->department?->hierarchyLabel() ?? '—' }}</td>
                        <td>{{ $nameOf($account->authority) }}</td>
                        <td>{{ $nameOf($account->service) }}</td>
                        <td><span class="lic-status lic-status--{{ $account->status }}">{{ __('gov_accounts.account_statuses.'.$account->status) }}</span></td>
                        <td>
                            <div class="lic-table__actions">
                                @if($canAct)
                                    <button type="button" class="lic-btn lic-btn--sm" data-bs-toggle="modal" data-bs-target="#govHrLifecycleModal" data-license-preview='@json($previewDetails)' aria-haspopup="dialog">
                                        <i class="bi bi-slash-circle"></i>{{ __('gov_accounts.hr.action') }}
                                    </button>
                                @else
                                    <span class="lic-help mb-0">{{ __('gov_accounts.hr.in_progress') }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="lic-empty">{{ __('gov_accounts.accounts.none') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $accounts->links('pagination.hm') }}
    </section>
</div>
@endsection
@push('modals')
    @include('gov-accounts.partials.hr-lifecycle-modal')
@endpush
@push('scripts')<script src="{{ asset('js/hm-licenses.js') }}?v={{ filemtime(public_path('js/hm-licenses.js')) }}"></script>@endpush
