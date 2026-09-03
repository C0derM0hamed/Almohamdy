{{ __('licenses.notifications.greeting', ['name' => $recipientName]) }}

{{ $messageText }}

@if($actionUrl)
{{ $actionLabel ?: __('licenses.notifications.open_record') }}: {{ $actionUrl }}
@endif
