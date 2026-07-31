@extends('layouts.app')

@php($editing = isset($user) && $user)
@section('title', $editing ? __('system_administration.users.edit') : __('system_administration.users.create'))

@section('content')
<div class="container-fluid py-3">
    <h1 class="h3 mb-3">{{ $editing ? __('system_administration.users.edit') : __('system_administration.users.create') }}</h1>
    <form method="post" action="{{ $editing ? route('modules.system-admin.users.update', $user->hr_id) : route('modules.system-admin.users.store') }}">
        @csrf @if($editing) @method('PUT') @endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="row g-3">
            @foreach(['hr_first_name', 'hr_last_name', 'hr_email_address', 'hr_username', 'mobile'] as $field)
                <div class="col-md-6"><label class="form-label" for="{{ $field }}">{{ __('system_administration.users.fields.'.$field) }}</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $editing ? $user->{$field} : '') }}" @required(in_array($field, ['hr_first_name','hr_email_address','hr_username']))></div>
            @endforeach
            <div class="col-md-6"><label class="form-label" for="password">{{ __('system_administration.users.fields.password') }}</label><input type="password" class="form-control" id="password" name="password" @required(!$editing) autocomplete="new-password"></div>
            <div class="col-md-6"><label class="form-label" for="password_confirmation">{{ __('system_administration.users.fields.password_confirmation') }}</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" @required(!$editing) autocomplete="new-password"></div>
            <div class="col-md-4"><label class="form-label" for="companies_groups_id">{{ __('system_administration.users.company') }}</label>@if($isSuperAdministrator)<select class="form-select" id="companies_groups_id" name="companies_groups_id">@foreach($companies as $company)<option value="{{ $company->id }}" @selected((int)old('companies_groups_id', $editing ? $user->companies_groups_id : session('companies_groups_id')) === (int)$company->id)>{{ $company->name_ar ?: $company->name_en }}</option>@endforeach</select>@else<input type="hidden" name="companies_groups_id" value="{{ session('companies_groups_id') }}"><input class="form-control" value="{{ session('companies_groups_id') }}" disabled>@endif</div>
            <div class="col-md-4"><label class="form-label" for="branch_id">{{ __('system_administration.users.branch') }}</label><select class="form-select" id="branch_id" name="branch_id" @disabled(!$isSuperAdministrator)>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((int)old('branch_id', $editing ? $user->branch_id : session('hr_branch_id')) === (int)$branch->id)>{{ $branch->name_ar ?: $branch->name_en }}</option>@endforeach</select>@if(!$isSuperAdministrator)<input type="hidden" name="branch_id" value="{{ session('hr_branch_id') }}">@endif</div>
            <div class="col-md-4"><label class="form-label" for="groupid">{{ __('system_administration.users.group') }}</label><select class="form-select" id="groupid" name="groupid"> <option value="0">{{ __('system_administration.users.no_group') }}</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected((int)old('groupid', $editing ? $user->groupid : 0) === (int)$group->id)>{{ $group->name_ar ?: $group->name_en }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label" for="hr_user_level">{{ __('system_administration.users.level') }}</label><select class="form-select" id="hr_user_level" name="hr_user_level">@foreach([0,1,2,3,4] as $level) @if($isSuperAdministrator || $level !== 3)<option value="{{ $level }}" @selected((int)old('hr_user_level', $editing ? $user->hr_user_level : 0) === $level)>{{ __('system_administration.users.levels.'.$level) }}</option>@endif @endforeach</select></div>
            <div class="col-md-6"><label class="form-label" for="activated">{{ __('system_administration.users.status') }}</label><select class="form-select" id="activated" name="activated"><option value="1" @selected(old('activated', $editing ? $user->activated : '1') === '1')>{{ __('system_administration.users.active') }}</option><option value="0" @selected(old('activated', $editing ? $user->activated : '1') === '0')>{{ __('system_administration.users.inactive') }}</option></select></div>
        </div>
        <h2 class="h5 mt-4">{{ __('system_administration.users.direct_permissions') }}</h2>
        <div class="row g-2">@foreach($permissionCatalog as $item) @php($page = $item->page) <div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $page }}" @checked(in_array($page, old('permissions', $directPermissions->all())))><span class="form-check-label" dir="ltr">{{ $page }}</span></label></div>@endforeach</div>
        @if($editing)<h2 class="h5 mt-4">{{ __('system_administration.users.inherited_permissions') }}</h2><p class="text-muted" dir="ltr">{{ $inheritedPermissions->join(', ') ?: __('system_administration.users.none') }}</p><h2 class="h5">{{ __('system_administration.users.effective_permissions') }}</h2><p dir="ltr">{{ $effectivePermissions->join(', ') ?: __('system_administration.users.none') }}</p>@endif
        <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('system_administration.users.save') }}</button><a class="btn btn-outline-secondary" href="{{ route('modules.system-admin.users.index') }}">{{ __('system_administration.users.cancel') }}</a></div>
    </form>
</div>
@endsection
