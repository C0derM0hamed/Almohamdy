@extends('layouts.app')
@section('title', 'صلاحيات المجموعة')
@section('figma_page_header', 'true')
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-fm hm-workflow">
    @include('layouts.partials.figma-module-header', ['crumbs' => [['label' => __('system_administration.title')], ['label' => __('system_administration.reference.groups')], ['label' => __('system_administration.users.permissions')]], 'title' => __('system_administration.users.permissions').': '.\App\Support\LocaleText::localizedValue($group->name_ar ?? null, $group->name_en ?? null), 'subtitle' => __('system_administration.users.inherited_permissions'), 'heroIconSrc' => asset('images/figma/workflows/system-users.svg'), 'heroIconSize' => 32])
    @if(session('success'))<div class="alert alert-success mt-3">{{ session('success') }}</div>@endif
    <form method="post" action="{{ route('modules.system-admin.reference.group-permissions.update', $group->id) }}" class="card border-0 shadow-sm mt-3"><div class="card-body">
        @php($currentGroup = null)
        <div class="row g-3">@forelse($catalog as $permission)
            @if($currentGroup !== $permission->group)<div class="col-12 mt-3"><h2 class="h6 border-bottom pb-2">{{ $permission->group ?: 'صلاحيات عامة' }}</h2></div>@php($currentGroup = $permission->group)@endif
            <div class="col-md-6 col-lg-4"><label class="d-flex gap-2 align-items-start border rounded p-2 h-100"><input type="checkbox" name="permissions[]" value="{{ $permission->page }}" @checked(in_array($permission->page, $selected, true))><span><strong>{{ \App\Support\LocaleText::localizedValue($permission->name_ar ?? null, $permission->name_en ?? null) ?: $permission->page }}</strong><small class="d-block text-muted" dir="ltr">{{ $permission->page }}</small></span></label></div>
        @empty<div class="col-12 text-muted">لا يوجد كتالوج صلاحيات متاح في قاعدة البيانات.</div>@endforelse</div>
    </div><div class="card-footer text-start">@csrf @method('PUT')<button class="btn btn-primary"><i class="bi bi-check2"></i> حفظ صلاحيات المجموعة</button><a class="btn btn-outline-secondary" href="{{ route('modules.system-admin.reference.index', 'groups') }}">رجوع</a></div></form>
</div>
@endsection
