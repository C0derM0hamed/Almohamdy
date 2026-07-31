@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
    $align = $isRtl ? 'right' : 'left';
    $logoPath = public_path('images/brand/hh-logo-horizontal-white.png');
    $fontStack = "'Noto Kufi Arabic','Segoe UI',Tahoma,Arial,sans-serif";
    $year = date('Y');
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ __('otp.email_subject') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!--[if mso]>
    <style type="text/css">
        body, table, td, div, p, a, span { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
</head>

<body style="margin:0;padding:0;background:#f8fafc;direction:{{ $dir }};-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

    <table role="presentation"
           width="100%"
           cellpadding="0"
           cellspacing="0"
           style="width:100%;border-collapse:collapse;background:#f8fafc;margin:0;padding:0;direction:{{ $dir }};font-family:{{ $fontStack }};">

        <tr>
            <td align="center" style="padding:28px 12px;">

                <table role="presentation"
                       width="640"
                       cellpadding="0"
                       cellspacing="0"
                       style="width:640px;max-width:100%;border-collapse:separate;border-spacing:0;background:#ffffff;border:1px solid #d7dee8;border-radius:22px;overflow:hidden;box-shadow:0 24px 60px rgba(0,38,84,0.14);">

                    {{-- Header --}}
                    <tr>
                        <td style="padding:0;background:#1855A4;background:linear-gradient(135deg,#1855A4 0%,#002654 100%);">

                            <table role="presentation"
                                   width="100%"
                                   cellpadding="0"
                                   cellspacing="0"
                                   style="width:100%;border-collapse:collapse;">

                                <tr>
                                    @if ($isRtl)
                                        <td style="padding:22px 24px 18px 24px;text-align:right;vertical-align:middle;">
                                            <div style="font-size:12px;font-weight:700;line-height:1.7;color:#CDFCFB;letter-spacing:.2px;">
                                                {{ __('otp.email_platform') }}
                                            </div>

                                            <div style="font-size:20px;font-weight:900;line-height:1.6;color:#ffffff;margin-top:2px;">
                                                {{ __('otp.email_heading') }}
                                            </div>

                                            <div style="font-size:12px;font-weight:500;line-height:1.7;color:rgba(255,255,255,.78);margin-top:2px;direction:ltr;text-align:right;">
                                                {{ __('otp.email_platform_en') }}
                                            </div>
                                        </td>

                                        <td width="190"
                                            style="width:190px;padding:22px 24px 18px 16px;text-align:left;vertical-align:middle;">
                                            <img src="{{ $message->embed($logoPath) }}"
                                                 alt="{{ __('dashboard.brand_name') }}"
                                                 width="176"
                                                 style="display:block;width:176px;max-width:176px;height:auto;border:0;outline:none;text-decoration:none;margin:0;" />
                                        </td>
                                    @else
                                        <td width="190"
                                            style="width:190px;padding:22px 16px 18px 24px;text-align:left;vertical-align:middle;">
                                            <img src="{{ $message->embed($logoPath) }}"
                                                 alt="{{ __('dashboard.brand_name') }}"
                                                 width="176"
                                                 style="display:block;width:176px;max-width:176px;height:auto;border:0;outline:none;text-decoration:none;margin:0;" />
                                        </td>

                                        <td style="padding:22px 24px 18px 24px;text-align:left;vertical-align:middle;">
                                            <div style="font-size:12px;font-weight:700;line-height:1.7;color:#CDFCFB;letter-spacing:.2px;">
                                                {{ __('otp.email_platform') }}
                                            </div>

                                            <div style="font-size:20px;font-weight:900;line-height:1.6;color:#ffffff;margin-top:2px;">
                                                {{ __('otp.email_heading') }}
                                            </div>
                                        </td>
                                    @endif
                                </tr>

                                <tr>
                                    <td colspan="2"
                                        style="height:5px;background:#4FCED2;font-size:0;line-height:0;">
                                        &nbsp;
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:30px 30px 10px 30px;color:#1f2937;text-align:{{ $align }};direction:{{ $dir }};">

                            <div style="font-size:17px;font-weight:900;line-height:1.8;color:#002654;margin:0 0 8px 0;">
                                {{ __('otp.email_greeting', ['name' => $recipientName]) }}
                            </div>

                            <div style="font-size:14px;font-weight:500;line-height:2;color:#64748b;margin:0;">
                                {{ __('otp.email_body') }}
                            </div>

                        </td>
                    </tr>

                    {{-- OTP Card --}}
                    <tr>
                        <td align="center" style="padding:18px 30px 20px 30px;">

                            <table role="presentation"
                                   cellpadding="0"
                                   cellspacing="0"
                                   style="border-collapse:separate;border-spacing:0;width:100%;max-width:430px;background:#ffffff;border:1px solid #A3BBDB;border-radius:18px;box-shadow:0 16px 34px rgba(24,85,164,0.12);">

                                <tr>
                                    <td style="padding:18px 20px 16px 20px;text-align:center;background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);border-radius:18px;">

                                        <div style="display:inline-block;padding:6px 14px;border-radius:999px;background:#CDFCFB;color:#1855A4;font-size:12px;font-weight:900;line-height:1.5;margin-bottom:12px;">
                                            {{ __('otp.email_code_label') }}
                                        </div>

                                        <div style="direction:ltr;text-align:center;font-family:'Segoe UI',Tahoma,Arial,sans-serif;font-size:40px;line-height:1.25;letter-spacing:10px;font-weight:900;color:#002654;margin:0;">
                                            {{ $code }}
                                        </div>

                                        <div style="height:1px;background:#E5E5E5;margin:16px auto 12px auto;width:86%;font-size:0;line-height:0;">
                                            &nbsp;
                                        </div>

                                        <div style="font-size:12px;font-weight:600;line-height:1.8;color:#969696;direction:{{ $dir }};">
                                            {{ __('otp.email_code_note') }}
                                        </div>

                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    {{-- Security Notice --}}
                    <tr>
                        <td style="padding:0 30px 28px 30px;">

                            <table role="presentation"
                                   width="100%"
                                   cellpadding="0"
                                   cellspacing="0"
                                   style="width:100%;border-collapse:separate;border-spacing:0;background:#f8fafc;border:1px solid #d7dee8;border-radius:14px;">

                                <tr>
                                    <td style="padding:14px 16px;text-align:{{ $align }};direction:{{ $dir }};">

                                        <div style="font-size:13px;font-weight:800;line-height:1.8;color:#1855A4;margin-bottom:3px;">
                                            {{ __('otp.email_security_title') }}
                                        </div>

                                        <div style="font-size:12px;font-weight:500;line-height:1.9;color:#64748b;">
                                            {{ __('otp.email_security_body') }}
                                        </div>

                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:16px 24px 22px 24px;background:#ffffff;border-top:1px solid #E5E5E5;text-align:center;">

                            <div style="font-size:12px;font-weight:600;line-height:1.9;color:#64748b;direction:{{ $dir }};">
                                {{ __('otp.email_auto_notice') }}
                            </div>

                            <div style="font-size:11px;font-weight:500;line-height:1.8;color:#969696;direction:{{ $dir }};">
                                {{ __('otp.email_copyright', ['year' => $year]) }}
                            </div>

                        </td>
                    </tr>

                </table>
            </td>
        </tr>

    </table>

</body>
</html>
