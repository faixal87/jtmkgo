@php
    $branding = app(\App\Support\BrandingSettings::class)->all();
    $systemTitle = $branding['system_title'] ?? config('app.name');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Happy Birthday, {{ $displayName }}!</title>
</head>
<body style="margin:0;padding:0;background:#f6f1ee;font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#2a1a1f;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0;padding:0;background:#f6f1ee;">
        <tr>
            <td align="center" style="padding:34px 14px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #eadce1;border-radius:20px;overflow:hidden;">
                    <tr>
                        <td style="background:#701a33;padding:28px 30px;color:#ffffff;border-bottom:4px solid #4e1223;">
                            <div style="font-family:Georgia,'Times New Roman',serif;font-size:26px;font-weight:700;letter-spacing:1.4px;">{{ $systemTitle }}</div>
                            <div style="margin-top:6px;font-size:12.5px;letter-spacing:.7px;color:#edd3dc;text-transform:uppercase;">A birthday note from NETIZEN of JTMK POLIMAS</div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:34px 30px 12px;">
                            <div style="display:inline-block;margin-bottom:18px;border-radius:999px;background:#f9edf1;color:#701a33;font-size:11.5px;font-weight:800;letter-spacing:.9px;padding:8px 14px;text-transform:uppercase;">Birthday Celebration</div>

                            @if ($photoPath)
                                <img src="{{ $message->embed($photoPath) }}" alt="{{ $displayName }}" width="150" style="display:block;width:150px;height:150px;object-fit:cover;border-radius:75px;border:5px solid #f9edf1;margin:0 auto 18px;">
                            @else
                                <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 18px;">
                                    <tr>
                                        <td align="center" style="width:150px;height:150px;border-radius:75px;background:#f9edf1;border:5px solid #eadce1;color:#701a33;font-family:Georgia,'Times New Roman',serif;font-size:42px;font-weight:700;line-height:150px;">
                                            {{ $initials }}
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <div style="font-size:34px;line-height:1;margin-bottom:10px;">&#127874;</div>
                            <h1 style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.25;color:#2a1a1f;font-weight:700;">Happy Birthday, {{ $displayName }}!</h1>
                            <p style="margin:14px auto 0;max-width:500px;font-size:15px;line-height:1.75;color:#5c4b50;">
                                Today we celebrate you and the quiet impact you bring to JTMK POLIMAS. May this new year of life be filled with good health, barakah, meaningful work, and moments that make the heart feel light.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 30px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0;background:#fbf7f8;border:1px solid #f0e2e6;border-radius:16px;">
                                <tr>
                                    <td style="padding:22px 24px;text-align:center;">
                                        <p style="margin:0 0 12px;font-family:Georgia,'Times New Roman',serif;font-size:20px;line-height:1.45;color:#701a33;font-weight:700;">A warm prayer for you</p>
                                        <p style="margin:0;font-size:15px;line-height:1.8;color:#5c4b50;">
                                            May Allah bless your age, protect your health, widen your rezeki, ease your affairs, and grant you happiness with your family, friends, and colleagues. May every effort you give return as goodness in this world and the hereafter.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 30px;background:#fbf7f8;border-top:1px solid #f0e2e6;font-size:12px;line-height:1.6;color:#96707d;">
                            This birthday greeting is generated automatically by {{ $systemTitle }}. Please do not reply to this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
