@extends('layouts.app')
@section('title', __('gov_accounts.requests.title'))
@section('sidebar_heading', __('gov_accounts.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')
<div class="hm-licenses">
@php($canRequest=\App\Support\GovAccounts\GovAccountPermissions::isAdministrator()||app(\App\Services\Auth\PermissionService::class)->can(\App\Support\GovAccounts\GovAccountPermissions::REQUEST))
@include('licenses.partials.page-header',['title'=>__('gov_accounts.requests.title'),'subtitle'=>__('gov_accounts.requests.subtitle'),'icon'=>'bi-file-earmark-person','actions'=>$canRequest ? new \Illuminate\Support\HtmlString('<a class="lic-btn lic-btn--primary" href="'.e(route('modules.gov-accounts.requests.create')).'"><i class="bi bi-plus-lg"></i>'.e(__('gov_accounts.requests.new')).'</a>') : null])
@include('licenses.partials.feedback')
<section class="lic-panel"><form method="GET" class="lic-form-grid" aria-label="{{ __('gov_accounts.actions.search') }}">
<select name="type" class="form-select"><option value="">{{ __('gov_accounts.export.type') }}</option>@foreach(array_keys(__('gov_accounts.types')) as $type)<option value="{{ $type }}" @selected(($filters['type']??null)===$type)>{{ __('gov_accounts.types.'.$type) }}</option>@endforeach</select>
<select name="status" class="form-select"><option value="">{{ __('gov_accounts.fields.status') }}</option>@foreach(array_keys(__('gov_accounts.statuses')) as $status)<option value="{{ $status }}" @selected(($filters['status']??null)===$status)>{{ __('gov_accounts.statuses.'.$status) }}</option>@endforeach</select>
<select name="created_by" class="form-select"><option value="">{{ __('gov_accounts.fields.user') }}</option>@foreach($employees as $employee)<option value="{{ $employee->getKey() }}" @selected(($filters['created_by']??null)==$employee->getKey())>{{ $employee->displayName() }}</option>@endforeach</select>
<select name="employee_user_id" class="form-select"><option value="">{{ __('gov_accounts.fields.employee') }}</option>@foreach($employees as $employee)<option value="{{ $employee->getKey() }}" @selected(($filters['employee_user_id']??null)==$employee->getKey())>{{ $employee->displayName() }}</option>@endforeach</select>
<select name="department_id" class="form-select"><option value="">{{ __('gov_accounts.fields.department') }}</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(($filters['department_id']??null)==$department->id)>{{ $department->localizedName() }}</option>@endforeach</select>
<select name="authority_id" class="form-select"><option value="">{{ __('gov_accounts.fields.authority') }}</option>@foreach($authorities as $authority)<option value="{{ $authority->id }}" @selected(($filters['authority_id']??null)==$authority->id)>{{ $authority->localizedName() }}</option>@endforeach</select>
<select name="service_id" class="form-select"><option value="">{{ __('gov_accounts.fields.service') }}</option>@foreach($services as $service)<option value="{{ $service->id }}" @selected(($filters['service_id']??null)==$service->id)>{{ $service->localizedName() }}</option>@endforeach</select>
<input type="date" name="date_from" class="form-control" value="{{ $filters['date_from']??'' }}" aria-label="{{ __('gov_accounts.fields.event_date') }}"><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to']??'' }}" aria-label="{{ __('gov_accounts.fields.event_date') }}">
<div><button class="lic-btn lic-btn--primary">{{ __('gov_accounts.actions.search') }}</button> <a class="lic-btn" href="{{ route('modules.gov-accounts.requests.index') }}">{{ __('gov_accounts.actions.cancel') }}</a></div>
</form></section>
<section class="lic-panel"><div class="lic-table-wrap"><table class="lic-table"><thead><tr><th>#</th><th>{{ __('gov_accounts.fields.employee') }}</th><th>{{ __('gov_accounts.fields.authority') }}</th><th>{{ __('gov_accounts.fields.service') }}</th><th>{{ __('gov_accounts.fields.status') }}</th><th>{{ __('gov_accounts.actions.actions') }}</th></tr></thead><tbody>
@forelse($requests as $record)<tr><td>{{ $record->id }}</td><td>{{ $record->employee?->displayName() }}</td><td>{{ $record->authority?->localizedName() }}</td><td>{{ $record->service?->localizedName() }}</td><td>{{ __('gov_accounts.statuses.'.$record->status) }}</td><td><a class="lic-btn lic-btn--sm" href="{{ route('modules.gov-accounts.requests.show',$record) }}">{{ __('gov_accounts.actions.view') }}</a></td></tr>@empty<tr><td colspan="6" class="lic-empty">{{ __('gov_accounts.admin.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $requests->links('pagination.hm') }}</section>
</div>
@endsection
