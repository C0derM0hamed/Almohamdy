@php
    $headerTitle = $title ?? __('licenses.title');
    $headerSubtitle = $subtitle ?? __('licenses.subtitle');
    $headerIcon = $icon ?? 'bi-patch-check';
@endphp
<header class="lic-page-head">
    <div class="lic-page-head__copy">
        <span class="lic-page-head__icon" aria-hidden="true"><i class="bi {{ $headerIcon }}"></i></span>
        <div>
            <h1>{{ $headerTitle }}</h1>
            @if ($headerSubtitle)<p>{{ $headerSubtitle }}</p>@endif
        </div>
    </div>
    @if (isset($actions) || isset($notificationItems))
        <div class="lic-page-actions lic-no-print">
            @if (isset($notificationItems))
                <details class="lic-notifications">
                    <summary class="lic-btn lic-notifications__trigger">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        <span>{{ __('licenses.notifications.my_notifications') }}</span>
                        @if (($notificationUnreadCount ?? 0) > 0)
                            <span class="lic-notification-count">{{ (int) $notificationUnreadCount }}</span>
                        @endif
                    </summary>
                    <section class="lic-notifications__popover" aria-label="{{ __('licenses.notifications.my_notifications') }}">
                        <header class="lic-notifications__head">
                            <strong>{{ __('licenses.notifications.my_notifications') }}</strong>
                            <span>{{ __('licenses.notifications.unread') }}: {{ (int) ($notificationUnreadCount ?? 0) }}</span>
                        </header>
                        <div class="lic-notifications__list">
                            @forelse ($notificationItems as $notification)
                                <article class="lic-notification-item {{ $notification->isRead() ? 'is-read' : 'is-unread' }}">
                                    <a class="lic-notification-item__link" href="{{ $notification->license ? route('modules.licenses.show', $notification->license) : route('modules.licenses.index') }}">
                                        <span class="lic-notification-item__icon"><i class="bi bi-bell{{ $notification->isRead() ? '' : '-fill' }}"></i></span>
                                        <span class="lic-notification-item__copy">
                                            <strong>{{ __('licenses.timeline.events.'.$notification->event_type) }}</strong>
                                            <small>{{ $notification->license?->license_number ?: $notification->license?->displayTitle() ?: '—' }}</small>
                                            <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->format('Y-m-d H:i') }}</time>
                                        </span>
                                    </a>
                                    @if (! $notification->isRead())
                                        <form method="POST" action="{{ route('modules.licenses.notifications.read', $notification) }}">
                                            @csrf
                                            <button type="submit" class="lic-notification-item__read">{{ __('licenses.notifications.mark_read') }}</button>
                                        </form>
                                    @endif
                                </article>
                            @empty
                                <div class="lic-notifications__empty">{{ __('licenses.notifications.empty') }}</div>
                            @endforelse
                        </div>
                    </section>
                </details>
            @endif
            {{ $actions ?? '' }}
        </div>
    @endif
</header>
