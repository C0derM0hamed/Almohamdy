<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><title>{{ $subjectLine }}</title></head>
<body style="margin:0;background:#f3f6f9;font-family:Arial,Tahoma,sans-serif;color:#1f2937">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td style="padding:32px 12px">
    <table role="presentation" width="620" cellspacing="0" cellpadding="0" align="center" style="max-width:100%;background:#fff;border-radius:12px;overflow:hidden">
        <tr><td style="padding:22px 28px;background:#0b5f56;color:#fff;font-size:20px;font-weight:bold">{{ $subjectLine }}</td></tr>
        <tr><td style="padding:28px;line-height:1.8">
            <p>{{ __('licenses.notifications.greeting', ['name' => $recipientName]) }}</p>
            <p>{!! nl2br(e($messageText)) !!}</p>
            @if($actionUrl)
                <p style="margin-top:26px"><a href="{{ $actionUrl }}" style="display:inline-block;padding:11px 20px;background:#b9913f;color:#fff;text-decoration:none;border-radius:7px">{{ $actionLabel ?: __('licenses.notifications.open_record') }}</a></p>
            @endif
        </td></tr>
    </table>
</td></tr></table>
</body>
</html>
