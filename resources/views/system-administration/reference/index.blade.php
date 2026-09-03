@extends('layouts.app')
@php($pageTitle = __('system_administration.reference.'.$spec['title']))
@section('title', $pageTitle)
@section('figma_page_header', 'true')
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-fm hm-workflow">
    @include('layouts.partials.figma-module-header', ['crumbs' => [['label' => __('dashboard.modules')], ['label' => $pageTitle]], 'title' => $pageTitle, 'subtitle' => __('system_administration.reference.scope'), 'heroIconSrc' => asset('images/figma/workflows/references.svg'), 'heroIconSize' => 32, 'actionUrl' => route('modules.system-admin.reference.create', $type), 'actionLabel' => __('system_administration.reference.create'), 'actionIconSrc' => asset('images/figma/technical-failures/add.svg')])
    <form class="wf-search-panel" method="get"><h2>{{ __('system_administration.search') }}</h2><div class="wf-filter-grid wf-filter-grid--two">
        <div class="wf-field"><label for="reference-search">{{ __('system_administration.reference.search') }}</label><input id="reference-search" name="search" value="{{ $search }}" placeholder="{{ __('system_administration.reference.search') }}"></div>
        <button class="wf-search-btn wf-search-btn--wide" type="submit"><i class="bi bi-search"></i> {{ __('system_administration.search') }}</button>
    </div></form>
    <section class="wf-table-panel wf-table-panel--contained">@include('layouts.partials.figma-workflow-table-head', ['title' => $pageTitle, 'items' => $rows])
        <div class="table-responsive"><table class="wf-table"><thead><tr><th>#</th><th>{{ __('system_administration.reference.name_ar') }}</th><th>{{ __('system_administration.reference.name_en') }}</th><th>{{ __('system_administration.reference.status') }}</th><th></th></tr></thead><tbody>
        @forelse($rows as $row)<tr><td><span class="wf-code">{{ $row->id }}</span></td><td>{{ $row->name_ar ?? '' }}</td><td>{{ \App\Support\LocaleText::localizedValue($row->name_ar ?? null, $row->name_en ?? null) }}</td><td><span class="wf-status">{{ isset($row->publish) && $row->publish ? __('system_administration.reference.published') : __('system_administration.reference.unpublished') }}</span></td><td><div class="wf-actions"><a class="wf-view" href="{{ route('modules.system-admin.reference.edit', [$type, $row->id]) }}" title="{{ __('system_administration.reference.edit') }}"><i class="bi bi-pencil"></i></a>@if(isset($row->publish))<form method="post" action="{{ route('modules.system-admin.reference.publish', [$type, $row->id]) }}">@csrf @method('PATCH')<button class="wf-view" title="{{ __('system_administration.reference.toggle') }}"><i class="bi bi-eye"></i></button></form>@endif<form method="post" action="{{ route('modules.system-admin.reference.destroy', [$type, $row->id]) }}">@csrf @method('DELETE')<button class="wf-view is-danger" title="{{ __('system_administration.reference.delete') }}"><i class="bi bi-trash"></i></button></form></div></td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-5">{{ __('system_administration.reference.empty') }}</td></tr>@endforelse
        </tbody></table></div><div class="p-3">{{ $rows->links() }}</div>
    </section>
</div>
@endsection
