@extends('layouts.app')

@section('title', __('licenses.notifications.my_notifications'))
@section('sidebar_heading', __('licenses.title'))
@section('sidebar_subheading', __('licenses.notifications.my_notifications'))
@section('figma_page_header', true)

@push('styles')
<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">
@endpush

@section('content')
<div class="hm-fm hm-licenses">
    @include('layouts.partials.figma-module-header', ['compact' => true, 'title' => __('licenses.notifications.my_notifications'), 'crumbs' => [['label' => __('dashboard.modules')], ['label' => __('licenses.title')], ['label' => __('licenses.notifications.my_notifications')]]])
    @include('licenses.partials.page-header', ['title' => __('licenses.notifications.my_notifications'), 'subtitle' => __('licenses.notifications.unread').': '.$unreadCount, 'icon' => 'bi-bell', 'actions' => new \Illuminate\Support\HtmlString('<a class="lic-btn" href="'.e(route('modules.licenses.index')).'"><i class="bi bi-list-check"></i>'.e(__('licenses.index')).'</a>')])
    @include('licenses.partials.feedback')

    <section class="lic-panel" aria-label="{{ __('licenses.notifications.my_notifications') }}">
        <div class="lic-table-wrap"><table class="lic-table"><thead><tr><th>{{ __('licenses.notifications.event') }}</th><th>{{ __('licenses.fields.license_number') }}</th><th>{{ __('licenses.notifications.sent_at') }}</th><th>{{ __('licenses.actions') }}</th></tr></thead><tbody>
        @forelse($notifications as $notification)
            <tr class="{{ $notification->isRead() ? '' : 'fw-bold' }}"><td>{{ __('licenses.timeline.events.'.$notification->event_type) }}</td><td>@if($notification->license)<a class="lic-table__primary" href="{{ route('modules.licenses.show', $notification->license) }}">{{ $notification->license->license_number ?: $notification->license->displayTitle() }}</a>@else — @endif</td><td>{{ $notification->created_at?->format('Y-m-d H:i') }}</td><td>@if(!$notification->isRead())<form method="POST" action="{{ route('modules.licenses.notifications.read', $notification) }}">@csrf<button class="lic-btn lic-btn--sm" type="submit">{{ __('licenses.notifications.mark_read') }}</button></form>@else — @endif</td></tr>
        @empty
            <tr><td colspan="4" class="lic-empty">{{ __('licenses.notifications.empty') }}</td></tr>
        @endforelse
        </tbody></table></div>
        @if(method_exists($notifications, 'links'))<div class="lic-pagination">{{ $notifications->links('pagination.hm') }}</div>@endif
    </section>
</div>
@endsection
