@extends('layouts.app')
@section('title', $account->username)
@section('sidebar_heading', __('gov_accounts.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')
<div class="hm-licenses">
@include('licenses.partials.page-header',['title'=>$account->username,'subtitle'=>__('gov_accounts.account_statuses.'.$account->status),'icon'=>'bi-person-vcard'])
<section class="lic-panel"><div class="lic-detail-grid">
<div><small>{{ __('gov_accounts.fields.employee') }}</small><strong>{{ $account->employee?->displayName() }}</strong></div><div><small>{{ __('gov_accounts.fields.branch') }}</small><strong>{{ $account->hospitalBranch?->localizedName() ?? '—' }}</strong></div><div><small>{{ __('gov_accounts.fields.department_unit') }}</small><strong>{{ $account->sourceRequest?->department?->hierarchyLabel() ?? '—' }}</strong></div><div><small>{{ __('gov_accounts.fields.authority') }}</small><strong>{{ $account->authority?->localizedName() }}</strong></div><div><small>{{ __('gov_accounts.fields.service') }}</small><strong>{{ $account->service?->localizedName() }}</strong></div><div><small>{{ __('gov_accounts.fields.role') }}</small><strong>{{ $account->role?->localizedName() }}</strong></div><div><small>{{ __('gov_accounts.fields.reference_no') }}</small><strong>{{ $account->reference_no ?: '—' }}</strong></div><div><small>{{ __('gov_accounts.fields.login_url') }}</small>@if($account->login_url)<a href="{{ $account->login_url }}" rel="noopener noreferrer" target="_blank">{{ $account->login_url }}</a>@else<strong>—</strong>@endif</div>
</div></section>
@if(($canCreateLifecycle??false) && isset($services) && !in_array($account->status,['closed','modification_requested','suspension_requested','closure_requested']))
<section class="lic-panel"><h3>{{ __('gov_accounts.lifecycle.title') }}</h3><form method="POST" action="{{ route('modules.gov-accounts.accounts.requests.store',$account) }}">@csrf<div class="lic-form-grid">
<select name="type" class="form-select" required>@foreach(['modify','permission_change','suspend','close'] as $type)<option value="{{ $type }}">{{ __('gov_accounts.types.'.$type) }}</option>@endforeach</select>
<select name="service_id" class="form-select">@foreach($services->where('authority_id',$account->authority_id) as $service)<option value="{{ $service->id }}" @selected($service->id===$account->service_id)>{{ $service->localizedName() }}</option>@endforeach</select>
<select name="requested_role_id" class="form-select"><option value="">—</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->localizedName() }}</option>@endforeach</select>
<textarea name="justification" class="form-control" required placeholder="{{ __('gov_accounts.fields.justification') }}"></textarea></div><button class="lic-btn lic-btn--primary">{{ __('gov_accounts.lifecycle.create') }}</button></form></section>
@endif
</div>
@endsection
