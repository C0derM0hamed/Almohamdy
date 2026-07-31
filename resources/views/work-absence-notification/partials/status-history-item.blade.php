@php
    $historyStage = match ($event['stage']) {
        'submitted' => 'submission',
        'action_processed', 'notification_activated' => 'hr',
        'memo_created', 'memo_recipients_assigned' => 'memo',
        'recipient_viewed' => 'recipient',
        default => $event['stage'],
    };
@endphp

<li class="hm-history-item">
    <span class="hm-history-item__dot" aria-hidden="true"></span>
    <div>
        <div class="hm-history-item__stage">
            {{ __('work_absence_notification.history.'.$historyStage) }}
        </div>
        <div class="hm-history-item__status">{{ $event['label'] }}</div>
        @if (in_array($event['stage'], ['action_processed', 'notification_activated'], true))
            @if ($event['user'] !== '' && $event['user'] !== '—')
                <div class="hm-history-item__meta">
                    {{ __('work_absence_notification.audit.user') }}: {{ $event['user'] }}
                </div>
            @endif
            @if ($event['action_type'] !== '' && $event['action_type'] !== '—')
                <div class="hm-history-item__meta">
                    {{ __('work_absence_notification.audit.action_type') }}: {{ $event['action_type'] }}
                </div>
            @endif
        @else
            @if ($event['actor'] !== '' && $event['actor'] !== '—')
                <div class="hm-history-item__meta">{{ $event['actor'] }}</div>
            @endif
            @if ($event['detail'] !== '')
                <div class="hm-history-item__meta">{{ $event['detail'] }}</div>
            @endif
        @endif
        <div class="hm-history-item__meta">{{ $event['at'] }}</div>
    </div>
</li>
