@extends('layouts.app')
@section('title', $notice->title)
@section('sidebar_heading', __('gov_accounts.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@php
    $nameOf = static function ($item) {
        if (! $item) return '—';
        if (method_exists($item, 'displayName')) return $item->displayName();
        if (method_exists($item, 'localizedName')) return $item->localizedName();
        $field = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        return data_get($item, $field) ?: data_get($item, 'name') ?: '—';
    };
    $dateOf = static fn ($value) => $value ? ($value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i') : substr((string) $value, 0, 16)) : '—';
    $dash = static fn ($value) => filled($value) ? $value : '—';
    $fileIcon = static function (?string $name): string {
        $ext = strtolower((string) pathinfo((string) $name, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'bi-file-earmark-pdf',
            'xls', 'xlsx' => 'bi-file-earmark-spreadsheet',
            'jpg', 'jpeg', 'png' => 'bi-file-earmark-image',
            default => 'bi-file-earmark',
        };
    };
    $fileSize = static function ($bytes): string {
        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return '—';
        }
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / 1048576, 1).' MB';
    };
    $statusKey = $notice->sent_at ? 'sent' : 'draft';
    $eventAt = trim(($notice->event_date?->format('Y-m-d') ?: '').' '.($notice->event_time ?: '')) ?: '—';
    $targetingMode = data_get($notice->targeting, 'mode') ?: 'all';
    $summary = [
        __('gov_accounts.fields.branch') => $nameOf($notice->hospitalBranch),
        __('gov_accounts.fields.authority') => $nameOf($notice->authority),
        __('gov_accounts.fields.service') => $nameOf($notice->service),
        __('gov_accounts.fields.event_date') => $eventAt,
        __('gov_accounts.fields.attendance_method') => $notice->attendance_method ? __('gov_accounts.attendance.'.$notice->attendance_method) : '—',
        __('gov_accounts.fields.targeting') => __('gov_accounts.targeting.'.$targetingMode),
        __('gov_accounts.fields.location') => $dash($notice->location),
        __('gov_accounts.fields.meeting_url') => $dash($notice->meeting_url),
    ];
@endphp
@section('content')
<div class="hm-licenses">
    @include('licenses.partials.page-header', [
        'title' => $notice->title,
        'subtitle' => __('gov_accounts.notices.subtitle'),
        'icon' => 'bi-calendar-event',
        'actions' => new \Illuminate\Support\HtmlString('<a class="lic-btn" href="'.e(route('modules.gov-accounts.notices.index')).'"><i class="bi bi-arrow-left"></i>'.e(__('gov_accounts.actions.back')).'</a>'),
    ])
    @include('licenses.partials.feedback')

    <section class="lic-panel">
        <div class="lic-panel__head">
            <h2 class="lic-panel__title"><i class="bi bi-info-circle"></i>{{ __('gov_accounts.notices.details') }}</h2>
            <span class="lic-status lic-status--{{ $statusKey }}">{{ __('gov_accounts.notices.'.$statusKey) }}</span>
        </div>
        <div class="lic-summary-grid">
            @foreach ($summary as $label => $value)
                <div class="lic-summary-item"><span class="lic-summary-item__label">{{ $label }}</span><span class="lic-summary-item__value {{ $label === __('gov_accounts.fields.meeting_url') ? 'lic-sensitive' : '' }}">{{ $value }}</span></div>
            @endforeach
        </div>
        @if($notice->description)
            <div class="mt-3"><span class="lic-label">{{ __('gov_accounts.fields.description') }}</span><p class="mb-0">{{ $notice->description }}</p></div>
        @endif
        @if($notice->notes)
            <div class="mt-3"><span class="lic-label">{{ __('gov_accounts.fields.notes') }}</span><p class="mb-0">{{ $notice->notes }}</p></div>
        @endif
        @if($notice->meeting_url)
            <div class="mt-3"><a class="lic-btn lic-btn--sm" href="{{ $notice->meeting_url }}" rel="noopener noreferrer" target="_blank"><i class="bi bi-box-arrow-up-right"></i>{{ __('gov_accounts.notices.join') }}</a></div>
        @endif
    </section>

    @if(! $notice->sent_at)
        <section class="lic-panel lic-action-hub lic-no-print" aria-labelledby="govNoticeActionsTitle">
            <div class="lic-panel__head">
                <div>
                    <h2 id="govNoticeActionsTitle" class="lic-panel__title"><i class="bi bi-sliders"></i>{{ __('gov_accounts.actions.actions') }}</h2>
                </div>
            </div>
            <div class="lic-action-grid">
                <a class="lic-btn lic-action-button" href="{{ route('modules.gov-accounts.notices.edit', $notice) }}"><i class="bi bi-pencil"></i>{{ __('gov_accounts.actions.edit') }}</a>
                <form method="post" action="{{ route('modules.gov-accounts.notices.send', $notice) }}">
                    @csrf
                    <button class="lic-btn lic-btn--primary lic-action-button" type="submit"><i class="bi bi-send"></i>{{ __('gov_accounts.notices.send') }}</button>
                </form>
            </div>
        </section>
    @endif

    <section class="lic-panel">
        <div class="lic-panel__head">
            <h2 class="lic-panel__title"><i class="bi bi-paperclip"></i>{{ __('gov_accounts.attachments') }}</h2>
            <span class="gov-file-count">{{ $notice->attachments->count() }}</span>
        </div>
        <form class="gov-file-upload" method="POST" enctype="multipart/form-data" action="{{ route('modules.gov-accounts.notices.attachments.store', $notice) }}">
            @csrf
            <span class="gov-file-upload__icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></span>
            <div class="gov-file-upload__copy">
                <strong>{{ __('gov_accounts.actions.upload') }}</strong>
                <small>{{ __('gov_accounts.requests.upload_hint') }}</small>
            </div>
            <input id="gov_notice_attachment" type="file" name="attachment" required accept=".pdf,.jpg,.jpeg,.png,.xls,.xlsx" aria-label="{{ __('gov_accounts.fields.file') }}">
            <button class="lic-btn lic-btn--primary" type="submit">{{ __('gov_accounts.actions.upload') }}</button>
        </form>
        <div class="gov-file-list">
            @forelse($notice->attachments->sortByDesc('id') as $attachment)
                <article class="gov-file-card">
                    <span class="gov-file-card__icon" aria-hidden="true"><i class="bi {{ $fileIcon($attachment->original_name) }}"></i></span>
                    <div class="gov-file-card__copy">
                        <a class="lic-table__primary" href="{{ route('modules.gov-accounts.notices.attachments.download', [$notice, $attachment]) }}" download="{{ $attachment->original_name }}">{{ $attachment->original_name }}</a>
                        <small>{{ $fileSize($attachment->size) }} · {{ $dateOf($attachment->uploaded_at) }}</small>
                    </div>
                    <a class="lic-btn lic-btn--sm" href="{{ route('modules.gov-accounts.notices.attachments.download', [$notice, $attachment]) }}" download="{{ $attachment->original_name }}">
                        <i class="bi bi-download" aria-hidden="true"></i>{{ __('gov_accounts.actions.download') }}
                    </a>
                </article>
            @empty
                <p class="gov-file-empty">{{ __('gov_accounts.notices.empty_attachments') }}</p>
            @endforelse
        </div>
    </section>

    <section class="lic-panel">
        <div class="lic-panel__head">
            <h2 class="lic-panel__title"><i class="bi bi-people"></i>{{ __('gov_accounts.notices.recipients') }}</h2>
            <span>{{ $notice->recipients->count() }}</span>
        </div>
        <div class="lic-table-wrap">
            <table class="lic-table lic-table--stack">
                <thead>
                    <tr>
                        <th>{{ __('gov_accounts.fields.employee') }}</th>
                        <th>{{ __('gov_accounts.fields.email') }}</th>
                        <th>{{ __('gov_accounts.fields.status') }}</th>
                        <th>{{ __('gov_accounts.fields.viewed_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($notice->recipients as $recipient)
                    @php
                        $recipientStatus = $recipient->viewed_at ? 'viewed' : ($recipient->sent_at ? 'not_viewed' : 'sent');
                    @endphp
                    <tr>
                        <td>{{ $recipient->user?->displayName() ?: '—' }}</td>
                        <td class="lic-sensitive">{{ $recipient->email }}</td>
                        <td><span class="lic-status lic-status--{{ $recipientStatus }}">{{ __('gov_accounts.notices.'.$recipientStatus) }}</span></td>
                        <td>{{ $dateOf($recipient->viewed_at) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="lic-empty">{{ __('gov_accounts.notices.no_recipients') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
