<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $subjectLine }}</title>
</head>
<body style="font-family: Tahoma, Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>{{ __('cc_notifications.greeting', ['name' => $recipientName]) }}</p>
    <p>{{ $intro }}</p>
    <p>
        <a href="{{ $replyUrl }}" style="display:inline-block;padding:10px 16px;background:#0f766e;color:#fff;text-decoration:none;border-radius:6px;">
            {{ __('cc_notifications.open_reply') }}
        </a>
    </p>
    <p style="font-size: 13px; color: #64748b;">{{ __('cc_notifications.link_fallback') }}<br>{{ $replyUrl }}</p>
</body>
</html>
