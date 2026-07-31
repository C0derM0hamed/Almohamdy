@extends('layouts.app')
@section('title', __('system_administration.users.details'))
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">{{ $user->displayName() }}</h1><a class="btn btn-primary" href="{{ route('modules.system-admin.users.edit', $user->hr_id) }}"><i class="bi bi-pencil" aria-hidden="true"></i> {{ __('system_administration.users.edit') }}</a></div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <dl class="row"><dt class="col-sm-3">{{ __('system_administration.users.username') }}</dt><dd class="col-sm-9" dir="ltr">{{ $user->hr_username }}</dd><dt class="col-sm-3">{{ __('system_administration.users.scope') }}</dt><dd class="col-sm-9">{{ $user->companies_groups_id }} / {{ $user->branch_id }}</dd><dt class="col-sm-3">{{ __('system_administration.users.group') }}</dt><dd class="col-sm-9">{{ $user->groupid ?: __('system_administration.users.no_group') }}</dd></dl>
    <h2 class="h5">{{ __('system_administration.users.direct_permissions') }}</h2><p dir="ltr">{{ $directPermissions->join(', ') ?: __('system_administration.users.none') }}</p>
    <h2 class="h5">{{ __('system_administration.users.inherited_permissions') }}</h2><p dir="ltr">{{ $inheritedPermissions->join(', ') ?: __('system_administration.users.none') }}</p>
    <h2 class="h5">{{ __('system_administration.users.effective_permissions') }}</h2><p dir="ltr">{{ $effectivePermissions->join(', ') ?: __('system_administration.users.none') }}</p>
</div>
@endsection
