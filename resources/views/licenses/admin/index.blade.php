@extends('layouts.app')

@section('title', __('licenses.admin.title'))
@section('sidebar_heading', __('licenses.title'))
@section('sidebar_subheading', __('licenses.admin.subtitle'))

@push('styles')
<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php $url=static fn($name,$params=[])=>\Illuminate\Support\Facades\Route::has($name)?route($name,$params):'#'; @endphp
<div class="hm-licenses">
    @include('licenses.partials.page-header',['title'=>__('licenses.admin.title'),'subtitle'=>__('licenses.admin.subtitle'),'icon'=>'bi-sliders','actions'=>new \Illuminate\Support\HtmlString('<a class="lic-btn" href="'.e($url('modules.licenses.index')).'"><i class="bi bi-arrow-left"></i>'.e(__('licenses.back')).'</a>')])
    @include('licenses.partials.feedback')
    <section class="lic-admin-grid" aria-label="{{ __('licenses.admin.title') }}">
        @foreach ([
            ['authorities','bi-bank',$authorityCount ?? data_get($counts ?? [],'authorities',0)],
            ['types','bi-patch-check',$typeCount ?? data_get($counts ?? [],'types',0)],
            ['stages','bi-signpost-split',$stageCount ?? data_get($counts ?? [],'stages',0)],
            ['escalation_groups','bi-people',$escalationGroupCount ?? data_get($counts ?? [],'escalation_groups',0)],
        ] as [$key,$icon,$count])
            @php $routeKey=str_replace('_','-',$key); @endphp
            <a class="lic-admin-card" href="{{ $url('modules.licenses.admin.'.$routeKey.'.index') }}"><span class="lic-admin-card__icon"><i class="bi {{ $icon }}"></i></span><h2>{{ __('licenses.admin.'.$key) }}</h2><p>{{ __('licenses.admin.subtitle') }}</p><strong class="lic-admin-card__count">{{ (int)$count }}</strong></a>
        @endforeach
    </section>
</div>
@endsection
