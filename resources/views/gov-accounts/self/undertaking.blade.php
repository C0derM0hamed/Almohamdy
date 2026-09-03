@extends('layouts.app')
@section('title', __('gov_accounts.undertakings.title'))
@section('sidebar_heading', __('gov_accounts.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')<div class="hm-licenses">@include('licenses.partials.page-header',['title'=>__('gov_accounts.undertakings.title'),'subtitle'=>$accountRequest->authority?->localizedName(),'icon'=>'bi-shield-check'])<section class="lic-panel"><p>{{ __('gov_accounts.undertakings.employee_text') }}</p><form method="POST" action="{{ route('modules.gov-accounts.undertakings.accept',$accountRequest) }}">@csrf<label class="lic-checkbox"><input type="checkbox" name="employee_undertaking" value="1" required><span>{{ __('gov_accounts.undertakings.confirm') }}</span></label><button class="lic-btn lic-btn--primary">{{ __('gov_accounts.actions.accept') }}</button></form></section></div>@endsection
