@extends('layouts.app')
@section('title', __('gov_accounts.references.'.$reference))
@section('sidebar_heading', __('gov_accounts.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')
@php($editing = (bool) $item)
<div class="hm-licenses">
    @include('licenses.partials.page-header', ['title' => $editing ? __('gov_accounts.actions.edit') : __('gov_accounts.actions.add'), 'subtitle' => __('gov_accounts.references.'.$reference), 'icon' => 'bi-pencil-square'])
    @include('licenses.partials.feedback')
    <section class="lic-panel"><form method="POST" action="{{ $editing ? route('modules.gov-accounts.admin.'.$reference.'.update', $item) : route('modules.gov-accounts.admin.'.$reference.'.store') }}">
        @csrf @if($editing) @method('PUT') @endif
        <div class="lic-form-grid">
            @if($reference === 'services')
                <div class="lic-field lic-field--span-2"><label for="authority_id">{{ __('gov_accounts.fields.authority') }}</label><select id="authority_id" name="authority_id" class="form-select" required><option value="">—</option>@foreach($authorities as $authority)<option value="{{ $authority->id }}" @selected((int)old('authority_id', $item?->authority_id) === (int)$authority->id)>{{ $authority->localizedName() }}</option>@endforeach</select>@error('authority_id')<div class="text-danger">{{ $message }}</div>@enderror</div>
            @endif
            @if($reference === 'department-heads')
                <div class="lic-field"><label for="department_id">{{ __('gov_accounts.fields.department') }}</label><select id="department_id" name="department_id" class="form-select" required><option value="">—</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((int)old('department_id', $item?->department_id) === (int)$department->id)>{{ $department->localizedName() }}</option>@endforeach</select>@error('department_id')<div class="text-danger">{{ $message }}</div>@enderror</div>
                <div class="lic-field"><label for="user_id">{{ __('gov_accounts.fields.user') }}</label><select id="user_id" name="user_id" class="form-select" required><option value="">—</option>@foreach($users as $user)<option value="{{ $user->getKey() }}" @selected((int)old('user_id', $item?->user_id) === (int)$user->getKey())>{{ $user->displayName() }}</option>@endforeach</select>@error('user_id')<div class="text-danger">{{ $message }}</div>@enderror</div>
            @else
                <div class="lic-field"><label for="name_ar">{{ __('gov_accounts.fields.name_ar') }}</label><input id="name_ar" name="name_ar" value="{{ old('name_ar', $item?->name_ar) }}" class="form-control" dir="rtl" required>@error('name_ar')<div class="text-danger">{{ $message }}</div>@enderror</div>
                <div class="lic-field"><label for="name_en">{{ __('gov_accounts.fields.name_en') }}</label><input id="name_en" name="name_en" value="{{ old('name_en', $item?->name_en) }}" class="form-control" dir="ltr" required>@error('name_en')<div class="text-danger">{{ $message }}</div>@enderror</div>
                <div class="lic-field"><label for="ranking">{{ __('gov_accounts.fields.ranking') }}</label><input id="ranking" type="number" min="0" name="ranking" value="{{ old('ranking', $item?->ranking ?? 0) }}" class="form-control"></div>
            @endif
            <div class="lic-field lic-field--span-2"><label class="lic-checkbox"><input type="checkbox" name="publish" value="1" @checked((bool)old('publish', $item?->publish ?? true))><span>{{ __('gov_accounts.fields.publish') }}</span></label></div>
        </div>
        <div class="lic-form-actions"><button class="lic-btn lic-btn--primary" type="submit">{{ __('gov_accounts.actions.save') }}</button><a class="lic-btn" href="{{ route('modules.gov-accounts.admin.'.$reference.'.index') }}">{{ __('gov_accounts.actions.cancel') }}</a></div>
    </form></section>
</div>
@endsection
