@extends('layouts.app')
@section('title', $config['title'])
@section('figma_page_header', 'true')
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-fm hm-workflow">
    @include('layouts.partials.figma-module-header', ['crumbs' => [['label' => __('dashboard.modules')], ['label' => $config['title']]], 'title' => $config['title'], 'subtitle' => __('employee_requests.scope'), 'heroIconSrc' => asset('images/figma/workflows/employee-request.svg'), 'heroIconSize' => 32, 'actionUrl' => route('modules.employee-requests.create', $type), 'actionLabel' => __('employee_requests.create'), 'actionIconSrc' => asset('images/figma/technical-failures/add.svg')])
    <section class="wf-table-panel wf-table-panel--contained">
        @include('layouts.partials.figma-workflow-table-head', ['title' => $config['title'], 'items' => $requests])
        <div class="table-responsive"><table class="wf-table"><thead><tr><th>{{ __('employee_requests.employee') }}</th><th>{{ __('employee_requests.date') }}</th><th>{{ __('employee_requests.reason') }}</th><th>{{ __('employee_requests.branch_status') }}</th><th>{{ __('employee_requests.hr_status') }}</th><th></th></tr></thead><tbody>
        @forelse($requests as $row)<tr><td><span class="wf-notice-cell"><span class="wf-avatar"><i class="bi bi-person"></i></span><strong>{{ $row->hr_username }} - {{ $row->hr_first_name }} {{ $row->hr_last_name }}</strong></span></td><td dir="ltr">{{ date('Y-m-d H:i',(int)$row->date) }}</td><td>{{ $row->reason }}</td><td><span class="wf-status">{{ $row->branch_reply?->status_name_ar ?: __('employee_requests.pending') }}</span></td><td><span class="wf-status">{{ $row->hr_reply?->status_name_ar ?: __('employee_requests.pending') }}</span></td><td><a class="wf-view" href="{{ route('modules.employee-requests.show',[$type,$row->id]) }}" aria-label="{{ __('technical_failures.view') }}"><i class="bi bi-eye"></i></a></td></tr>
        @empty<tr><td colspan="6" class="text-center text-muted py-5">{{ __('employee_requests.empty') }}</td></tr>@endforelse
        </tbody></table></div><div class="p-3">{{ $requests->links() }}</div>
    </section>
</div>
@endsection
