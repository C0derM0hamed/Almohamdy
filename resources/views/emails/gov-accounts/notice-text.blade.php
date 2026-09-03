{{ __('gov_accounts.email.greeting', ['name' => $recipientName]) }}

{{ $notice->title }}
{{ $notice->description }}
{{ __('gov_accounts.fields.event_date') }}: {{ $notice->event_date?->format('Y-m-d') }} {{ $notice->event_time }}

{{ __('gov_accounts.notices.open') }}: {{ $viewUrl }}
