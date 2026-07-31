@php
    $hasRecipients = $recipientStats['total'] > 0;
@endphp

<div class="hm-wan-detail-card wan-info-card wan-info-card--panel">
    <div class="wan-info-card__head">
        <span class="wan-info-card__icon wan-info-card__icon--recipients" aria-hidden="true"><i class="bi bi-people"></i></span>
        <h2>{{ __('work_absence_notification.recipients.section_title') }}</h2>
    </div>

    <div class="hm-wan-recipient-stats" aria-label="{{ __('work_absence_notification.recipients.statistics') }}">
        <div class="hm-wan-recipient-stats__item">
            <span class="hm-wan-recipient-stats__label">{{ __('work_absence_notification.stats.recipients_total') }}</span>
            <span class="hm-wan-recipient-stats__value">{{ $recipientStats['total'] }}</span>
        </div>
        <div class="hm-wan-recipient-stats__item hm-wan-recipient-stats__item--viewed">
            <span class="hm-wan-recipient-stats__label">{{ __('work_absence_notification.stats.recipients_viewed') }}</span>
            <span class="hm-wan-recipient-stats__value">{{ $recipientStats['viewed'] }}</span>
        </div>
        <div class="hm-wan-recipient-stats__item hm-wan-recipient-stats__item--pending">
            <span class="hm-wan-recipient-stats__label">{{ __('work_absence_notification.stats.recipients_pending_view') }}</span>
            <span class="hm-wan-recipient-stats__value">{{ $recipientStats['pending_view'] }}</span>
        </div>
    </div>

    <h3 class="hm-wan-recipient-list__heading">{{ __('work_absence_notification.recipients.list') }}</h3>

    @if ($hasRecipients)
        <div class="hm-wan-table-wrap">
            <table class="hm-wan-table">
                <thead>
                    <tr>
                        <th>{{ __('work_absence_notification.recipients.fields.recipient') }}</th>
                        <th>{{ __('work_absence_notification.recipients.fields.memo_type') }}</th>
                        <th>{{ __('work_absence_notification.recipients.fields.memo_date') }}</th>
                        <th>{{ __('work_absence_notification.recipients.fields.status') }}</th>
                        <th>{{ __('work_absence_notification.recipients.fields.seen_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($notification->memos as $memo)
                        @foreach ($memo->recipients as $recipient)
                            <tr>
                                <td>{{ $recipient->recipient?->displayName() ?? '#'.$recipient->user_id }}</td>
                                <td>{{ $memo->memoTypeLabel() }}</td>
                                <td>{{ $memo->formattedDate() }}</td>
                                <td>
                                    <span class="hm-wan-recipient-badge hm-wan-recipient-badge--{{ $recipient->seenStatusKey() }}">
                                        {{ $recipient->seenStatusLabel() }}
                                    </span>
                                </td>
                                <td>{{ $recipient->formattedSeenAt() }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="hm-wan-detail-card__empty">{{ __('work_absence_notification.recipients.no_recipients') }}</p>
    @endif
</div>
