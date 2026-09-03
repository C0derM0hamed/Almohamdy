@extends('layouts.app')
@section('title', __('gov_accounts.notices.title'))
@section('sidebar_heading', __('gov_accounts.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')<div class="hm-licenses">
@include('licenses.partials.page-header',['title'=>__('gov_accounts.notices.title'),'subtitle'=>__('gov_accounts.notices.subtitle'),'icon'=>'bi-calendar-event','actions'=>new \Illuminate\Support\HtmlString('<a class="lic-btn lic-btn--primary" href="'.e(route('modules.gov-accounts.notices.create')).'"><i class="bi bi-plus-lg"></i>'.e(__('gov_accounts.notices.new')).'</a>')])
@include('licenses.partials.feedback')
<section class="lic-panel"><div class="lic-table-wrap"><table class="lic-table"><thead><tr><th>#</th><th>{{ __('gov_accounts.fields.title') }}</th><th>{{ __('gov_accounts.fields.authority') }}</th><th>{{ __('gov_accounts.fields.event_date') }}</th><th>{{ __('gov_accounts.fields.status') }}</th><th>{{ __('gov_accounts.actions.actions') }}</th></tr></thead><tbody>
@forelse($notices as $record)<tr><td>{{ $record->id }}</td><td>{{ $record->title }}</td><td>{{ $record->authority?->localizedName() }}</td><td>{{ $record->event_date?->format('Y-m-d') }} {{ $record->event_time }}</td><td>{{ $record->sent_at ? __('gov_accounts.notices.sent') : __('gov_accounts.notices.draft') }}</td><td><a class="lic-btn lic-btn--sm" href="{{ route('modules.gov-accounts.notices.show',$record) }}">{{ __('gov_accounts.actions.view') }}</a></td></tr>@empty<tr><td colspan="6" class="lic-empty">{{ __('gov_accounts.notices.none') }}</td></tr>@endforelse
</tbody></table></div>{{ $notices->links('pagination.hm') }}</section></div>@endsection
