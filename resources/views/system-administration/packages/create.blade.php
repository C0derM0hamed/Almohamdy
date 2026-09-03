@extends('layouts.app')

@section('title', 'إضافة خدمة')
@section('sidebar_heading', __('system_administration.title'))
@section('sidebar_subheading', __('system_administration.packages_subtitle'))

@section('content')
    <div class="hm-dda hm-dda--package-form">
        @include('doctors-directory-admin.partials.dda-breadcrumb', ['items' => [
            ['label' => __('system_administration.dashboard'), 'url' => route('modules.system-admin.dashboard')],
            ['label' => __('system_administration.packages'), 'url' => route('modules.system-admin.packages.index')],
            ['label' => 'إضافة خدمة', 'chip' => true],
        ]])
        @include('doctors-directory-admin.partials.dda-page-hero', ['title' => 'إضافة خدمة', 'subtitle' => 'إضافة كل بيانات الخدمة ومرفقاتها'])
        <div class="dda-form-panel">
            @include('system-administration.packages._form', ['package' => null, 'sectionOptions' => $sectionOptions, 'isCreate' => true])
        </div>
    </div>
@endsection
