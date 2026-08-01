@extends('layouts.app')

@section('title', __('emergency_follow_up.title'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div><h1 class="h4 mb-1">{{ __('emergency_follow_up.title') }}</h1><p class="text-muted mb-0">{{ __('emergency_follow_up.subtitle') }}</p></div>
        <a class="btn btn-primary" href="{{ route('modules.emergency-follow-up.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('emergency_follow_up.add') }}</a>
    </div>

    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>{{ __('emergency_follow_up.date') }}</th><th>{{ __('emergency_follow_up.file_number') }}</th><th>{{ __('emergency_follow_up.notice') }}</th><th>{{ __('emergency_follow_up.description') }}</th><th>{{ __('emergency_follow_up.action') }}</th><th>{{ __('emergency_follow_up.latest_notice') }}</th><th>{{ __('emergency_follow_up.actions') }}</th></tr></thead>
                <tbody>
                @forelse ($followUps as $followUp)
                    <tr>
                        <td dir="ltr">{{ date('Y-m-d', (int) $followUp->date) }}</td>
                        <td>{{ $followUp->file_number }}</td>
                        <td>{{ app()->getLocale() === 'ar' ? $followUp->noticeType?->name_ar : $followUp->noticeType?->name_en }}</td>
                        <td>{{ $followUp->description }}</td>
                        <td>{{ $followUp->action }}</td>
                        <td>{{ $followUp->latestNotice?->notice ?: '—' }}</td>
                        <td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="{{ route('modules.emergency-follow-up.show', $followUp->id) }}" title="{{ __('emergency_follow_up.open') }}"><i class="bi bi-eye" aria-hidden="true"></i></a> <a class="btn btn-sm btn-outline-secondary" href="{{ route('modules.emergency-follow-up.print', $followUp->id) }}" title="{{ __('emergency_follow_up.print') }}"><i class="bi bi-printer" aria-hidden="true"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">{{ __('emergency_follow_up.empty') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($followUps->hasPages())<div class="card-footer bg-transparent">{{ $followUps->links() }}</div>@endif
    </div>
@endsection
