@php
    $branding = app(\App\Support\BrandingSettings::class)->all();
    $systemTitle = $branding['system_title'] ?? config('app.name');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $systemTitle }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f6f1ee; font-family:'Segoe UI', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f6f1ee; padding:34px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; background-color:#ffffff; border:1px solid #eadce1; border-radius:20px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#701a33; padding:28px 30px; color:#ffffff; border-bottom:4px solid #4e1223;">
                            <div style="font-family:Georgia,'Times New Roman',serif; font-size:26px; font-weight:700; letter-spacing:1.4px;">{{ $systemTitle }}</div>
                            <div style="margin-top:6px; color:#edd3dc; font-size:12.5px; letter-spacing:.7px; text-transform:uppercase;">Jabatan Teknologi Maklumat &amp; Komunikasi &middot; POLIMAS</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 30px; background-color:#fbf7f8; border-top:1px solid #f0e2e6;">
                            <span style="color:#96707d; font-size:12px; line-height:1.6;">{!! $branding['footer_text'] ?? $systemTitle !!} &middot; This is an automated message, please do not reply.</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
