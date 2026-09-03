@extends('layouts.app')
@section('title', __('gov_accounts.accounts.title'))
@section('sidebar_heading', __('gov_accounts.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')
<div class="hm-licenses">
@include('licenses.partials.page-header',['title'=>__('gov_accounts.accounts.title'),'subtitle'=>__('gov_accounts.accounts.subtitle'),'icon'=>'bi-person-vcard'])
<section class="lic-panel"><form method="GET" class="lic-form-grid" aria-label="{{ __('gov_accounts.actions.search') }}">
<select name="employee_user_id" class="form-select"><option value="">{{ __('gov_accounts.fields.employee') }}</option>@foreach($employees as $employee)<option value="{{ $employee->getKey() }}" @selected(($filters['employee_user_id']??null)==$employee->getKey())>{{ $employee->displayName() }}</option>@endforeach</select>
<select name="department_id" class="form-select"><option value="">{{ __('gov_accounts.fields.department') }}</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(($filters['department_id']??null)==$department->id)>{{ $department->localizedName() }}</option>@endforeach</select>
<select name="authority_id" class="form-select"><option value="">{{ __('gov_accounts.fields.authority') }}</option>@foreach($authorities as $authority)<option value="{{ $authority->id }}" @selected(($filters['authority_id']??null)==$authority->id)>{{ $authority->localizedName() }}</option>@endforeach</select>
<select name="service_id" class="form-select"><option value="">{{ __('gov_accounts.fields.service') }}</option>@foreach($services as $service)<option value="{{ $service->id }}" @selected(($filters['service_id']??null)==$service->id)>{{ $service->localizedName() }}</option>@endforeach</select>
<select name="role_id" class="form-select"><option value="">{{ __('gov_accounts.fields.role') }}</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected(($filters['role_id']??null)==$role->id)>{{ $role->localizedName() }}</option>@endforeach</select>
<select name="status" class="form-select"><option value="">{{ __('gov_accounts.fields.status') }}</option>@foreach(array_keys(__('gov_accounts.account_statuses')) as $status)<option value="{{ $status }}" @selected(($filters['status']??null)===$status)>{{ __('gov_accounts.account_statuses.'.$status) }}</option>@endforeach</select>
<div><button class="lic-btn lic-btn--primary">{{ __('gov_accounts.actions.search') }}</button> <a class="lic-btn" href="{{ route('modules.gov-accounts.accounts.index') }}">{{ __('gov_accounts.actions.cancel') }}</a></div>
</form></section>
<section class="lic-panel"><div class="lic-table-wrap"><table class="lic-table"><thead><tr><th>{{ __('gov_accounts.fields.employee') }}</th><th>{{ __('gov_accounts.fields.authority') }}</th><th>{{ __('gov_accounts.fields.service') }}</th><th>{{ __('gov_accounts.fields.username') }}</th><th>{{ __('gov_accounts.fields.status') }}</th></tr></thead><tbody>
@forelse($accounts as $account)<tr><td><a href="{{ route('modules.gov-accounts.accounts.show',$account) }}">{{ $account->employee?->displayName() }}</a></td><td>{{ $account->authority?->localizedName() }}</td><td>{{ $account->service?->localizedName() }}</td><td>{{ $account->username }}</td><td>{{ __('gov_accounts.account_statuses.'.$account->status) }}</td></tr>@empty<tr><td colspan="5" class="lic-empty">{{ __('gov_accounts.accounts.none') }}</td></tr>@endforelse
</tbody></table></div>{{ $accounts->links('pagination.hm') }}</section>
</div>
@endsection
