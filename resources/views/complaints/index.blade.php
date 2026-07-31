@extends('layouts.app')

@section('title', __('complaints.list'))

@push('styles')
    <link href="{{ asset('css/hm-complaints-redesign.css') }}?v={{ filemtime(public_path('css/hm-complaints-redesign.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-cp hm-cp--detail">
        @include('complaints.partials.cp-breadcrumb', [
            'items' => [
                ['label' => __('complaints.dashboard'), 'url' => route('modules.complaints')],
                ['label' => __('complaints.list'), 'chip' => true],
            ],
        ])

        <header class="cp-detail-head">
            <div class="cp-detail-head__copy">
                <h1>{{ __('complaints.list') }}</h1>
                <p class="cp-page-subtitle">{{ __('complaints.list_subtitle') }}</p>
            </div>
        </header>

        <section class="cp-panel" aria-labelledby="complaintsListTitle">
            <h2 id="complaintsListTitle" class="visually-hidden">{{ __('complaints.list') }}</h2>

            @include('complaints.partials.cp-search', [
                'filters' => $filters,
                'statusOptions' => $statusOptions,
                'hasFilters' => $hasFilters,
                'searchAction' => route('modules.complaints.index'),
                'resetUrl' => route('modules.complaints.index'),
                'searchFormClass' => 'cp-search--legacy',
            ])

            @include('complaints.partials.cp-results', [
                'complaints' => $complaints,
                'hasFilters' => $hasFilters,
            ])
        </section>
    </div>
@endsection
