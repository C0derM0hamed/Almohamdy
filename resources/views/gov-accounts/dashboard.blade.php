@extends('layouts.app')
@section('title', __('gov_accounts.dashboard.title'))
@section('sidebar_heading', __('gov_accounts.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')
@php
    $statusMeta = [
        'draft' => ['icon' => 'bi-pencil-square', 'tone' => 'muted'],
        'awaiting_employee' => ['icon' => 'bi-hourglass-split', 'tone' => 'amber'],
        'under_review' => ['icon' => 'bi-search', 'tone' => 'blue'],
        'rejected' => ['icon' => 'bi-x-octagon', 'tone' => 'red'],
        'approved' => ['icon' => 'bi-check2-circle', 'tone' => 'green'],
        'submitted_to_authority' => ['icon' => 'bi-send', 'tone' => 'violet'],
        'completed' => ['icon' => 'bi-patch-check', 'tone' => 'navy'],
        'cancelled' => ['icon' => 'bi-slash-circle', 'tone' => 'muted'],
    ];
    $typeMeta = [
        'create' => ['icon' => 'bi-plus-circle', 'tone' => 'blue'],
        'modify' => ['icon' => 'bi-pencil', 'tone' => 'violet'],
        'permission_change' => ['icon' => 'bi-shield-lock', 'tone' => 'amber'],
        'suspend' => ['icon' => 'bi-pause-circle', 'tone' => 'red'],
        'close' => ['icon' => 'bi-x-circle', 'tone' => 'navy'],
    ];
@endphp
<div class="hm-licenses">
@include('licenses.partials.page-header',['title'=>__('gov_accounts.dashboard.title'),'subtitle'=>__('gov_accounts.dashboard.subtitle'),'icon'=>'bi-speedometer2'])
@if(app(\App\Services\Auth\PermissionService::class)->can(\App\Support\GovAccounts\GovAccountPermissions::EXPORT))<section class="lic-panel lic-no-print"><h2 class="lic-panel__title">{{ __('gov_accounts.export.title') }}</h2><div class="d-flex flex-wrap gap-2">@foreach(['accounts','requests','notices'] as $report)@foreach(['csv','xls'] as $format)<a class="lic-btn lic-btn--sm" href="{{ route('modules.gov-accounts.export',['format'=>$format,'report'=>$report]) }}"><i class="bi bi-download"></i>{{ __('gov_accounts.export.'.$report) }} ({{ strtoupper($format) }})</a>@endforeach @endforeach</div></section>@endif
<section class="lic-stat-grid lic-stat-grid--five lic-stat-grid--compact" aria-label="{{ __('gov_accounts.dashboard.accounts') }}">
@foreach([['total','bi-person-vcard',''],['active','bi-check-circle','active'],['suspended','bi-pause-circle','warning'],['closed','bi-x-circle','danger']] as [$key,$icon,$tone])<article class="lic-stat {{ $tone ? 'lic-stat--'.$tone : '' }}"><span class="lic-stat__icon"><i class="bi {{ $icon }}"></i></span><span class="lic-stat__copy"><span class="lic-stat__label">{{ __('gov_accounts.dashboard.'.$key) }}</span><strong class="lic-stat__value">{{ (int) data_get($metrics,'accounts.'.$key,0) }}</strong></span></article>@endforeach
<article class="lic-stat lic-stat--violet"><span class="lic-stat__icon"><i class="bi bi-people"></i></span><span class="lic-stat__copy"><span class="lic-stat__label">{{ __('gov_accounts.dashboard.multi_account_employees') }}</span><strong class="lic-stat__value">{{ (int) $metrics['multi_account_employees'] }}</strong></span></article>
</section>
<div class="lic-two-column lic-two-column--even">
    @include('gov-accounts.partials.breakdown-list', ['title' => __('gov_accounts.dashboard.requests_by_status'), 'icon' => 'bi-flag', 'items' => $metrics['requests_by_status'] ?? [], 'filterKey' => 'status', 'meta' => $statusMeta])
    @include('gov-accounts.partials.breakdown-list', ['title' => __('gov_accounts.dashboard.requests_by_type'), 'icon' => 'bi-layers', 'items' => $metrics['requests_by_type'] ?? [], 'filterKey' => 'type', 'meta' => $typeMeta])
</div>
<section class="lic-panel"><h2 class="lic-panel__title">{{ __('gov_accounts.dashboard.notices') }}</h2><div class="lic-finance-grid lic-finance-grid--compact">@foreach(['sent','recipients','viewed','view_rate'] as $key)<article class="lic-mini-stat"><span>{{ __('gov_accounts.dashboard.notice_'.$key) }}</span><strong>{{ data_get($metrics,'notices.'.$key,0) }}@if($key==='view_rate')% @endif</strong></article>@endforeach</div></section>
</div>
@endsection
