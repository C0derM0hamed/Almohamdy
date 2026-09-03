@extends('layouts.app')
@php($pageTitle = $direction === 'incoming' ? __('transferal.incoming') : __('transferal.outgoing'))
@section('title', $pageTitle)
@section('figma_page_header', 'true')
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-fm hm-workflow">
    @include('layouts.partials.figma-module-header', ['crumbs' => [['label' => __('dashboard.modules')], ['label' => $pageTitle]], 'title' => $pageTitle, 'subtitle' => __('transferal.scope'), 'heroIconSrc' => asset('images/figma/workflows/transferal.svg'), 'heroIconSize' => 32, 'actionUrl' => $direction === 'outgoing' ? route('modules.transferal.create') : null, 'actionLabel' => $direction === 'outgoing' ? __('transferal.create') : null, 'actionIconSrc' => asset('images/figma/technical-failures/add.svg')])
    <form class="wf-search-panel" method="get"><h2>{{ __('transferal.search') }}</h2><div class="wf-filter-grid wf-filter-grid--three">
        <div class="wf-field"><label for="transfer-file">{{ __('transferal.file_number') }}</label><input id="transfer-file" name="file_number" value="{{ $filters['file_number'] }}"></div>
        <div class="wf-field"><label for="transfer-from">{{ __('transferal.from') }}</label><span class="wf-date-wrap"><input id="transfer-from" type="date" name="from" value="{{ $filters['from'] }}"><img src="{{ asset('images/figma/technical-failures/calendar.svg') }}" alt=""></span></div>
        <div class="wf-field"><label for="transfer-to">{{ __('transferal.to') }}</label><span class="wf-date-wrap"><input id="transfer-to" type="date" name="to" value="{{ $filters['to'] }}"><img src="{{ asset('images/figma/technical-failures/calendar.svg') }}" alt=""></span></div>
        <button class="wf-search-btn" type="submit" aria-label="{{ __('transferal.search') }}"><i class="bi bi-search"></i></button>
    </div></form>
    <section class="wf-table-panel wf-table-panel--contained">@include('layouts.partials.figma-workflow-table-head', ['title' => $pageTitle, 'items' => $transfers])
        <div class="table-responsive"><table class="wf-table"><thead><tr><th>{{ __('transferal.date') }}</th><th>{{ __('transferal.patient') }}</th><th>{{ __('transferal.file_number') }}</th><th>{{ $direction === 'incoming' ? __('transferal.source') : __('transferal.target') }}</th><th>{{ __('transferal.status') }}</th><th></th></tr></thead><tbody>
        @forelse($transfers as $item)<tr><td dir="ltr">{{ date('Y-m-d H:i', (int)$item->date) }}</td><td>{{ $item->patient_name }}</td><td><span class="wf-code">{{ $item->file_number }}</span></td><td>{{ $direction === 'incoming' ? $item->source_name_ar : $item->target_name_ar }}</td><td><span class="wf-status">@if($item->refusal){{ __('transferal.refused') }}@elseif($item->receive){{ __('transferal.received') }}@elseif($item->approval){{ __('transferal.approved') }}@elseif($item->confirm){{ __('transferal.confirmed') }}@else{{ __('transferal.new') }}@endif</span></td><td><a class="wf-view" href="{{ route('modules.transferal.show', $item->id) }}" title="{{ __('transferal.open') }}"><i class="bi bi-eye"></i></a></td></tr>
        @empty<tr><td colspan="6" class="text-center text-muted py-5">{{ __('transferal.empty') }}</td></tr>@endforelse
        </tbody></table></div><div class="p-3">{{ $transfers->links() }}</div>
    </section>
</div>
@endsection
