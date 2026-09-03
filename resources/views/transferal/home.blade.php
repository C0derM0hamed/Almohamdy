@extends('layouts.app')
@php($homeTitle = app()->getLocale() === 'ar' ? 'تحويل واستقبال الحالات' : 'Transfer and Receive Cases')
@php($homeScope = app()->getLocale() === 'ar' ? 'إدارة وتنظيم عمليات تحويل الحالات واستقبالها بين المنشآت الصحية.' : 'Manage and organize case transfers and reception between healthcare facilities.')
@section('title', $homeTitle)
@section('figma_page_header', 'true')
@push('workflow_styles')
    <link href="{{ asset('css/hm-transferal-home.css') }}?v={{ filemtime(public_path('css/hm-transferal-home.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-fm hm-transferal-home">
    @include('layouts.partials.figma-module-header', [
        'crumbs' => [['label' => __('dashboard.modules')], ['label' => $homeTitle]],
        'title' => $homeTitle,
        'subtitle' => $homeScope,
        'heroIcon' => 'bi-arrow-left-right',
        'actionUrl' => route('modules.emergency-follow-up.create'),
        'actionLabel' => __('emergency_follow_up.add'),
        'actionIcon' => 'bi-plus-lg',
    ])
    <section class="transferal-choice-grid" aria-label="{{ $homeTitle }}">
        <a class="transferal-choice" href="{{ route('modules.transferal.outgoing') }}">
            <span class="transferal-choice__icon" aria-hidden="true"><i class="bi bi-file-earmark-arrow-up transferal-choice__icon--outgoing"></i></span>
            <strong>{{ __('transferal.outgoing') }}</strong>
        </a>
        <a class="transferal-choice" href="{{ route('modules.transferal.incoming') }}">
            <span class="transferal-choice__icon" aria-hidden="true"><i class="bi bi-inbox"></i></span>
            <strong>{{ __('transferal.incoming') }}</strong>
        </a>
    </section>
</div>
@endsection
