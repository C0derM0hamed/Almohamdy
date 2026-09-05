@extends('layouts.app')
@section('title', __('gov_accounts.notices.title'))
@section('sidebar_heading', __('gov_accounts.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')
@php
    $nameOf = static function ($item) {
        if (! $item) return '—';
        if (method_exists($item, 'displayName')) return $item->displayName();
        if (method_exists($item, 'localizedName')) return $item->localizedName();
        $field = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        return data_get($item, $field) ?: data_get($item, 'name') ?: '—';
    };
@endphp
<div class="hm-licenses">
    @include('licenses.partials.page-header', [
        'title' => __('gov_accounts.notices.title'),
        'subtitle' => __('gov_accounts.notices.subtitle'),
        'icon' => 'bi-calendar-event',
        'actions' => new \Illuminate\Support\HtmlString('<a class="lic-btn lic-btn--primary" href="'.e(route('modules.gov-accounts.notices.create')).'"><i class="bi bi-plus-lg"></i>'.e(__('gov_accounts.notices.new')).'</a>'),
    ])
    @include('licenses.partials.feedback')
    <section class="lic-panel">
        <div class="lic-panel__head">
            <h2 class="lic-panel__title"><i class="bi bi-calendar-week"></i>{{ __('gov_accounts.notices.title') }}</h2>
            <span>{{ $notices->total() }}</span>
        </div>
        <div class="lic-table-wrap">
            <table class="lic-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('gov_accounts.fields.title') }}</th>
                        <th>{{ __('gov_accounts.fields.authority') }}</th>
                        <th>{{ __('gov_accounts.fields.event_date') }}</th>
                        <th>{{ __('gov_accounts.fields.status') }}</th>
                        <th>{{ __('gov_accounts.actions.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($notices as $record)
                    @php
                        $statusKey = $record->sent_at ? 'sent' : 'draft';
                        $eventAt = trim(($record->event_date?->format('Y-m-d') ?: '').' '.($record->event_time ?: '')) ?: '—';
                        $previewDetails = [
                            'number' => '#'.$record->id,
                            'title' => $record->title,
                            'authority' => $nameOf($record->authority),
                            'branch' => $nameOf($record->hospitalBranch),
                            'event_at' => $eventAt,
                            'attendance' => $record->attendance_method ? __('gov_accounts.attendance.'.$record->attendance_method) : '—',
                            'targeting' => __('gov_accounts.targeting.'.(data_get($record->targeting, 'mode') ?: 'all')),
                            'service' => $nameOf($record->service),
                            'status' => __('gov_accounts.notices.'.$statusKey),
                            'status_key' => $statusKey,
                            'url' => route('modules.gov-accounts.notices.show', $record),
                        ];
                    @endphp
                    <tr>
                        <td><button type="button" class="lic-table__primary lic-table__primary--button" data-bs-toggle="modal" data-bs-target="#govNoticeQuickViewModal" data-license-preview='@json($previewDetails)'>#{{ $record->id }}</button></td>
                        <td>{{ $record->title }}<span class="lic-table__sub">{{ $nameOf($record->hospitalBranch) }}</span></td>
                        <td>{{ $nameOf($record->authority) }}</td>
                        <td>{{ $eventAt }}</td>
                        <td><span class="lic-status lic-status--{{ $statusKey }}">{{ __('gov_accounts.notices.'.$statusKey) }}</span></td>
                        <td><button type="button" class="lic-btn lic-btn--sm" data-bs-toggle="modal" data-bs-target="#govNoticeQuickViewModal" data-license-preview='@json($previewDetails)' aria-haspopup="dialog"><i class="bi bi-eye"></i>{{ __('gov_accounts.actions.view') }}</button></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="lic-empty">{{ __('gov_accounts.notices.none') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $notices->links('pagination.hm') }}
    </section>
</div>
@endsection
@push('modals')
    @include('gov-accounts.partials.notice-quick-view-modal')
@endpush
@push('scripts')<script src="{{ asset('js/hm-licenses.js') }}?v={{ filemtime(public_path('js/hm-licenses.js')) }}"></script>@endpush
