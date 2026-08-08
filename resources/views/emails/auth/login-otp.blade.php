@php
    $logoPath = public_path('images/brand/hh-logo-horizontal-white.png');
    $year = date('Y');
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('otp.email_subject', [], 'ar') }}</title>
</head>

<body style="margin:0;padding:0;background:#f3f6fa;direction:rtl;font-family:Tahoma,Arial,sans-serif;">

    <table role="presentation"
           width="100%"
           cellpadding="0"
           cellspacing="0"
           style="width:100%;border-collapse:collapse;background:#f3f6fa;">

        <tr>
            <td align="center" style="padding:30px 12px;">

                <table role="presentation"
                       width="620"
                       cellpadding="0"
                       cellspacing="0"
                       style="width:620px;max-width:100%;background:#ffffff;border:1px solid #d7e1ee;border-radius:22px;overflow:hidden;border-collapse:separate;">

                    <tr>
                        <td style="padding:22px 26px;background:#1855a4;border-bottom:4px solid #55d2d8;">

                            <table role="presentation"
                                   width="100%"
                                   cellpadding="0"
                                   cellspacing="0"
                                   style="border-collapse:collapse;">

                                <tr>
                                    <td align="right"
                                        style="color:#ffffff;vertical-align:middle;">

                                        <div style="font-size:13px;font-weight:700;line-height:1.8;">
                                            نظام تقييم الزائر السري
                                        </div>

                                        <div style="font-size:11px;color:#dceaff;line-height:1.6;">
                                            Mystery Visitor Evaluation System
                                        </div>

                                    </td>

                                    <td align="left" style="vertical-align:middle;">

                                        <img src="{{ $message->embed($logoPath) }}"
                                             width="190"
                                             alt="{{ __('dashboard.brand_name', [], 'ar') }}"
                                             style="display:block;width:190px;max-width:100%;height:auto;border:0;">

                                    </td>
                                </tr>

                            </table>

                        </td>
                    </tr>

                    <tr>
                        <td style="padding:30px 34px 26px;">

                            <div style="font-size:25px;font-weight:800;color:#002654;line-height:1.6;margin-bottom:18px;">
                                رمز التحقق لتسجيل الدخول
                            </div>

                            <div style="font-size:17px;font-weight:700;color:#15345f;line-height:1.8;margin-bottom:8px;">
                                {{ $recipientName }}
                            </div>

                            <div style="font-size:14px;color:#64748b;line-height:2;margin-bottom:24px;">
                                استخدم رمز التحقق التالي لإكمال تسجيل الدخول.
                            </div>

                            <table role="presentation"
                                   width="100%"
                                   cellpadding="0"
                                   cellspacing="0"
                                   style="border-collapse:collapse;">

                                <tr>
                                    <td align="center">

                                        <table role="presentation"
                                               width="400"
                                               cellpadding="0"
                                               cellspacing="0"
                                               style="width:400px;max-width:100%;border:1px solid #aac3e2;border-radius:18px;background:#fbfdff;border-collapse:separate;">

                                            <tr>
                                                <td align="center" style="padding:18px 20px 8px;">

                                                    <span style="display:inline-block;padding:7px 15px;border-radius:999px;background:#d8f8f8;color:#1855a4;font-size:12px;font-weight:800;">
                                                        رمز تحقق لمرة واحدة
                                                    </span>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td align="center"
                                                    dir="ltr"
                                                    style="padding:12px 20px;font-family:'Segoe UI',Tahoma,Arial,sans-serif;font-size:42px;font-weight:900;letter-spacing:9px;color:#002654;">

                                                    {{ $code }}

                                                </td>
                                            </tr>

                                            <tr>
                                                <td align="center"
                                                    style="padding:8px 20px 22px;font-size:12px;color:#8995a8;line-height:1.9;">

                                                    الرمز صالح لمدة {{ $expiryMinutes }} دقائق ولا يتم مشاركته مع أي شخص.

                                                </td>
                                            </tr>

                                        </table>

                                    </td>
                                </tr>

                            </table>

                            <div style="margin-top:22px;padding:18px 20px;background:#f7faff;border:1px solid #dce7f3;border-radius:15px;">

                                <div style="font-size:15px;font-weight:800;color:#1855a4;margin-bottom:6px;">
                                    تنبيه أمني
                                </div>

                                <div style="font-size:12px;color:#64748b;line-height:1.9;">
                                    إذا لم تطلب هذا الرمز، تجاهل الرسالة ولا تشارك بيانات الدخول أو رمز التحقق مع أي جهة.
                                </div>

                            </div>

                            <div style="margin-top:24px;padding-top:17px;border-top:1px solid #e5ebf3;text-align:center;font-size:11px;color:#98a2b3;line-height:1.9;">
                                هذه رسالة آلية، الرجاء عدم الرد عليها.
                                <br>
                                © {{ $year }} مستشفيات الحمادي
                            </div>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>

    </table>

</body>
</html>
