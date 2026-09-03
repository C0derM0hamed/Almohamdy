<!doctype html><html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"><body>
<p>{{ __('gov_accounts.email.greeting', ['name' => $recipientName]) }}</p>
<h2>{{ $notice->title }}</h2>
<p>{{ $notice->description }}</p>
<p>{{ __('gov_accounts.fields.event_date') }}: {{ $notice->event_date?->format('Y-m-d') }} {{ $notice->event_time }}</p>
<p><a href="{{ $viewUrl }}">{{ __('gov_accounts.notices.open') }}</a></p>
</body></html>
