@extends('layouts.app')
@php($pageTitle = $type === 'manual' ? __('admission_calculator.manual') : __('admission_calculator.standard'))
@section('title', $pageTitle)
@section('figma_page_header', 'true')
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-fm hm-workflow">
    @include('layouts.partials.figma-module-header', ['crumbs' => [['label' => __('dashboard.modules')], ['label' => $pageTitle]], 'title' => $pageTitle, 'subtitle' => __('admission_calculator.scope'), 'heroIconSrc' => asset('images/figma/workflows/calculator.svg'), 'heroIconSize' => 32, 'actionUrl' => route('modules.admission-calculator.create', $type), 'actionLabel' => __('admission_calculator.create'), 'actionIconSrc' => asset('images/figma/technical-failures/add.svg')])
    <form class="wf-search-panel" method="get">
        <h2>{{ __('admission_calculator.search') }}</h2>
        <div class="wf-filter-grid wf-filter-grid--three">
            <div class="wf-field"><label for="admission-file">{{ __('admission_calculator.file_number') }}</label><input id="admission-file" name="file_number" value="{{ $filters['file_number'] }}"></div>
            <div class="wf-field"><label for="admission-from">{{ __('admission_calculator.from') }}</label><span class="wf-date-wrap"><input id="admission-from" type="date" name="from" value="{{ $filters['from'] }}"><img src="{{ asset('images/figma/technical-failures/calendar.svg') }}" alt=""></span></div>
            <div class="wf-field"><label for="admission-to">{{ __('admission_calculator.to') }}</label><span class="wf-date-wrap"><input id="admission-to" type="date" name="to" value="{{ $filters['to'] }}"><img src="{{ asset('images/figma/technical-failures/calendar.svg') }}" alt=""></span></div>
            <button class="wf-search-btn" type="submit" aria-label="{{ __('admission_calculator.search') }}"><i class="bi bi-search"></i></button>
        </div>
    </form>
    <section class="wf-table-panel wf-table-panel--contained">
        @include('layouts.partials.figma-workflow-table-head', ['title' => $pageTitle, 'items' => $records])
        <div class="table-responsive"><table class="wf-table"><thead><tr><th>{{ __('admission_calculator.date') }}</th><th>{{ __('admission_calculator.patient') }}</th><th>{{ __('admission_calculator.file_number') }}</th><th>{{ __('admission_calculator.days') }}</th><th>{{ __('admission_calculator.room') }}</th><th></th></tr></thead><tbody>
        @forelse($records as $item)<tr><td dir="ltr">{{ date('Y-m-d H:i',(int)$item->date) }}</td><td>{{ $item->patient_name }}</td><td><span class="wf-code">{{ $item->file_number }}</span></td><td>{{ $item->days }}</td><td>{{ $item->room }}</td><td><a class="wf-view" href="{{ route('modules.admission-calculator.show',[$type,$item->id]) }}" aria-label="{{ __('technical_failures.view') }}"><i class="bi bi-eye"></i></a></td></tr>
        @empty<tr><td colspan="6" class="text-center text-muted py-5">{{ __('admission_calculator.empty') }}</td></tr>@endforelse
        </tbody></table></div><div class="p-3">{{ $records->links() }}</div>
    </section>
</div>
@endsection
