@extends('layouts.app')

@php
    $editing = isset($user) && $user;
@endphp
@section('title', $editing ? __('system_administration.users.edit') : __('system_administration.users.create'))
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@push('scripts')
    <script defer src="{{ asset('js/hm-direct-permissions.js') }}?v={{ filemtime(public_path('js/hm-direct-permissions.js')) }}"></script>
    <script defer src="{{ asset('js/hm-user-form.js') }}?v={{ filemtime(public_path('js/hm-user-form.js')) }}"></script>
@endpush

@section('content')
@php
    $selectedDirectPermissions = collect(old('permissions', $directPermissions->all()));
    $permissionCategories = collect(config('permissions.categories', []))->keyBy('code');
    $permissionGroups = collect($permissionCatalog)->groupBy('category');
@endphp
<div class="container-fluid py-3 hm-user-form">
    <h1 class="h3 mb-3">{{ $editing ? __('system_administration.users.edit') : __('system_administration.users.create') }}</h1>
    <form method="post" action="{{ $editing ? route('modules.system-admin.users.update', $user->hr_id) : route('modules.system-admin.users.store') }}">
        @csrf @if($editing) @method('PUT') @endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="row g-3">
            @foreach(['hr_first_name', 'hr_last_name', 'hr_email_address', 'hr_username', 'mobile'] as $field)
                <div class="col-md-6">
                    <label class="form-label" for="{{ $field }}">{{ __('system_administration.users.fields.'.$field) }}</label>
                    <input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $editing ? $user->{$field} : '') }}" @if($field === 'mobile') type="tel" inputmode="numeric" data-number-boxes="false" maxlength="10" pattern="05[0-9]{8}" autocomplete="tel" @endif @required(in_array($field, ['hr_first_name','hr_email_address','hr_username']))>
                </div>
            @endforeach
            <div class="col-md-6"><label class="form-label" for="password">{{ __('system_administration.users.fields.password') }}</label><input type="password" class="form-control" id="password" name="password" @required(!$editing) autocomplete="new-password"></div>
            <div class="col-md-6"><label class="form-label" for="password_confirmation">{{ __('system_administration.users.fields.password_confirmation') }}</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" @required(!$editing) autocomplete="new-password"></div>
            <div class="col-md-4"><label class="form-label" for="companies_groups_id">{{ __('system_administration.users.company') }}</label>@if($isSuperAdministrator)<select class="form-select" id="companies_groups_id" name="companies_groups_id">@foreach($companies as $company)<option value="{{ $company->id }}" @selected((int)old('companies_groups_id', $editing ? $user->companies_groups_id : session('companies_groups_id')) === (int)$company->id)>{{ $company->name_ar ?: $company->name_en }}</option>@endforeach</select>@else<input type="hidden" name="companies_groups_id" value="{{ session('companies_groups_id') }}"><input class="form-control" value="{{ session('companies_groups_id') }}" disabled>@endif</div>
            @php
                $selectedBranches = collect(old('branch_ids', $selectedBranchIds ?? ($editing ? [$user->branch_id] : [session('hr_branch_id')])))->map(fn ($id) => (int) $id)->filter()->values();
                $primaryBranch = $selectedBranches->first() ?: (int) session('hr_branch_id');
            @endphp
            <div class="col-md-4">
                <label class="form-label" for="branch_ids">{{ __('system_administration.users.branch') }}</label>
                <div class="hm-branch-picker" data-branch-picker data-selected-template="{{ __('system_administration.users.branch_selected') }}" data-placeholder="{{ __('system_administration.users.branch_placeholder') }}">
                    <button type="button" class="hm-branch-picker__toggle" id="branch_ids" aria-haspopup="listbox" aria-expanded="false" data-branch-picker-toggle @disabled(!$isSuperAdministrator)>
                        <span data-branch-picker-summary>{{ $selectedBranches->isNotEmpty() ? __('system_administration.users.branch_selected', ['count' => $selectedBranches->count()]) : __('system_administration.users.branch_placeholder') }}</span>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="hm-branch-picker__menu" data-branch-picker-menu hidden role="listbox" aria-multiselectable="true" aria-labelledby="branch_ids">
                        @forelse($branches as $branch)
                            <label class="hm-branch-picker__option">
                                <input type="checkbox" value="{{ $branch->id }}" data-branch-picker-option @checked($selectedBranches->contains((int) $branch->id)) @disabled(!$isSuperAdministrator)>
                                <span>{{ $branch->name_ar ?: $branch->name_en }}</span>
                            </label>
                        @empty
                            <span class="hm-branch-picker__empty">لا توجد أقسام متاحة.</span>
                        @endforelse
                    </div>
                    <div data-branch-picker-values>
                        @foreach($selectedBranches as $selectedBranch)
                            <input type="hidden" name="branch_ids[]" value="{{ $selectedBranch }}">
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="branch_id" value="{{ $primaryBranch }}">
                <small class="form-text text-muted">{{ __('system_administration.users.branch_help') }}</small>
            </div>
            {{-- User groups remain in the legacy schema for compatibility, but
                 permissions are assigned to this account directly. --}}
            <input type="hidden" name="groupid" value="{{ old('groupid', $editing ? $user->groupid : 0) }}">
            <div class="col-md-6"><label class="form-label" for="hr_user_level">{{ __('system_administration.users.level') }}</label><select class="form-select" id="hr_user_level" name="hr_user_level">@foreach([0,1,2,3,4] as $level) @if($isSuperAdministrator || $level !== 3)<option value="{{ $level }}" @selected((int)old('hr_user_level', $editing ? $user->hr_user_level : 0) === $level)>{{ __('system_administration.users.levels.'.$level) }}</option>@endif @endforeach</select></div>
            <div class="col-md-6"><label class="form-label" for="activated">{{ __('system_administration.users.status') }}</label><select class="form-select" id="activated" name="activated"><option value="1" @selected(old('activated', $editing ? $user->activated : '1') === '1')>{{ __('system_administration.users.active') }}</option><option value="0" @selected(old('activated', $editing ? $user->activated : '1') === '0')>{{ __('system_administration.users.inactive') }}</option></select></div>
        </div>
        <section class="hm-direct-permissions" data-direct-permissions>
            <header class="hm-direct-permissions__head">
                <div>
                    <span class="hm-direct-permissions__eyebrow">إدارة الوصول</span>
                    <h2>{{ __('system_administration.users.direct_permissions') }}</h2>
                    <p>حدد الصلاحيات المباشرة لهذا الحساب وافتح القسم المطلوب فقط.</p>
                </div>
                <div class="hm-direct-permissions__tools">
                    <label class="hm-direct-permissions__search"><i class="bi bi-search"></i><input type="search" placeholder="ابحث في الصلاحيات..." data-direct-permission-search></label>
                    <span class="hm-direct-permissions__count"><strong data-direct-selected-count>{{ $selectedDirectPermissions->count() }}</strong> محددة</span>
                </div>
            </header>
            <div class="hm-direct-permissions__groups">
                @foreach($permissionGroups as $categoryCode => $items)
                    @php
                        $category = $permissionCategories->get($categoryCode, [
                            'label' => 'صلاحيات قديمة',
                            'description' => 'صلاحيات محفوظة للتوافق مع البيانات السابقة',
                            'icon' => 'bi-clock-history',
                        ]);
                    @endphp
                    <section class="hm-direct-group" data-direct-category>
                        <header class="hm-direct-group__head">
                            <label class="hm-direct-group__select-all">
                                <input type="checkbox" data-direct-category-select-all aria-label="تحديد كل صلاحيات قسم {{ $category['label'] }}">
                            </label>
                            <button type="button" class="hm-direct-group__toggle" aria-expanded="false" aria-label="فتح أو إغلاق قسم {{ $category['label'] }}" data-direct-category-toggle>
                                <span class="hm-direct-group__title">
                                    <span class="hm-direct-group__icon"><i class="bi {{ $category['icon'] }}"></i></span>
                                    <span><strong>{{ $category['label'] }}</strong><small>{{ $category['description'] }}</small></span>
                                </span>
                                <span class="hm-direct-group__meta"><span data-direct-category-count>0/{{ $items->count() }}</span><i class="bi bi-chevron-down"></i></span>
                            </button>
                        </header>
                        <div class="hm-direct-group__body" data-direct-category-body hidden>
                            @foreach($items as $item)
                                @php
                                    $page = $item['code'];
                                @endphp
                                <label class="hm-direct-permission" data-direct-permission-item data-search="{{ mb_strtolower($item['label'].' '.($item['description'] ?? '').' '.$page) }}">
                                    <input type="checkbox" name="permissions[]" value="{{ $page }}" data-direct-permission-check @checked($selectedDirectPermissions->contains($page))>
                                    <span class="hm-direct-permission__icon"><i class="bi {{ $item['icon'] ?? 'bi-shield-check' }}"></i></span>
                                    <span class="hm-direct-permission__copy"><strong>{{ $item['label'] }}</strong><small>{{ $item['description'] ?? '' }}</small><span class="visually-hidden">{{ $page }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </section>
        <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('system_administration.users.save') }}</button><a class="btn btn-outline-secondary" href="{{ route('modules.system-admin.users.index') }}">{{ __('system_administration.users.cancel') }}</a></div>
    </form>
</div>
@endsection
