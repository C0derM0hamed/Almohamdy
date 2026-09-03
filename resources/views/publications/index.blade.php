@extends('layouts.app')
@section('title', __('publications.title'))
@section('figma_page_header', 'true')
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-fm hm-workflow">
    @include('layouts.partials.figma-module-header', ['crumbs' => [['label' => __('dashboard.modules')], ['label' => __('publications.title')]], 'title' => __('publications.title'), 'subtitle' => __('publications.scope'), 'heroIconSrc' => asset('images/figma/workflows/publications.svg'), 'heroIconSize' => 32, 'actionUrl' => route('modules.publications.create'), 'actionLabel' => __('publications.create'), 'actionIconSrc' => asset('images/figma/technical-failures/add.svg')])
    <form class="wf-search-panel" method="get"><h2>{{ __('publications.search') }}</h2><div class="wf-filter-grid wf-filter-grid--two">
        <div class="wf-field"><label for="publication-search">{{ __('publications.search') }}</label><input id="publication-search" name="search" value="{{ $filters['search'] }}"></div>
        <div class="wf-field"><label for="publication-type">{{ __('publications.type') }}</label><span class="wf-select-wrap"><select id="publication-type" name="type_id"><option value="">{{ __('publications.all') }}</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected($filters['type_id'] === $type->id)>{{ $type->name_ar }}</option>@endforeach</select><img src="{{ asset('images/figma/technical-failures/select.svg') }}" alt=""></span></div>
        <button class="wf-search-btn wf-search-btn--wide" type="submit"><i class="bi bi-search"></i> {{ __('publications.search') }}</button>
    </div></form>
    <section class="wf-table-panel wf-table-panel--contained">@include('layouts.partials.figma-workflow-table-head', ['title' => __('publications.title'), 'items' => $posts])
        <div class="table-responsive"><table class="wf-table"><thead><tr><th>#</th><th>{{ __('publications.subject') }}</th><th>{{ __('publications.type') }}</th><th>{{ __('publications.branch') }}</th><th>{{ __('publications.date') }}</th><th></th></tr></thead><tbody>
        @forelse($posts as $post)<tr><td><span class="wf-code">{{ $post->id }}</span></td><td>{{ $post->subject_ar }}</td><td><span class="wf-status">{{ $post->type_name_ar }}</span></td><td>{{ $post->branch_name_ar }}</td><td>{{ $post->created_at ?? '' }}</td><td><a class="wf-view" href="{{ route('modules.publications.show', $post->id) }}" title="{{ __('publications.open') }}"><i class="bi bi-eye"></i></a></td></tr>
        @empty<tr><td colspan="6" class="text-center text-muted py-5">{{ __('publications.empty') }}</td></tr>@endforelse
        </tbody></table></div><div class="p-3">{{ $posts->links() }}</div>
    </section>
</div>
@endsection
