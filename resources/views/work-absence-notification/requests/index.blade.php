@extends('layouts.app')
@section('title', __('work_absence_notification.request.title'))
@section('content')
<div class="hm-hs hm-wan">
 <div class="d-flex justify-content-between align-items-center mb-3"><h1>{{ __('work_absence_notification.request.title') }}</h1><a class="hs-btn hs-btn--primary" href="{{ route('modules.work-absence.requests.create') }}">{{ __('work_absence_notification.request.create') }}</a></div>
 @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
 <div class="table-responsive"><table class="table"><thead><tr><th>#</th><th>{{ __('work_absence_notification.request.type') }}</th><th>{{ __('work_absence_notification.request.begin') }}</th><th>{{ __('work_absence_notification.request.status') }}</th><th></th></tr></thead><tbody>
 @forelse($notifications as $notification)<tr><td>{{ $notification->id }}</td><td>{{ $notification->notificationTypeLabel() }}</td><td>{{ $notification->formattedBeginDate() }}</td><td>{{ $notification->workflowStatusLabel() }}</td><td>@if($notification->hasAttachment())<a href="{{ $notification->protectedAttachmentUrl() }}">{{ __('work_absence_notification.request.attachment') }}</a>@endif</td></tr>@empty<tr><td colspan="5">{{ __('work_absence_notification.request.empty') }}</td></tr>@endforelse
 </tbody></table></div>{{ $notifications->links() }}
</div>
@endsection
