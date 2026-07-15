@extends('emails.layout')

@section('content')
    <div style="display:inline-block;margin-bottom:16px;border-radius:999px;background:#f9edf1;color:#701a33;font-size:11.5px;font-weight:800;letter-spacing:.8px;padding:8px 14px;text-transform:uppercase;">Account Security</div>

    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;color:#2a1a1f;font-size:23px;line-height:1.35;font-weight:700;">Reset Your Password</h1>

    <p style="margin:0 0 14px;color:#5c4b50;font-size:15px;line-height:1.75;">
        Salam {{ $user->name }}, we received a request to reset the password for your JTMK Go account.
    </p>

    <p style="margin:0 0 24px;color:#5c4b50;font-size:15px;line-height:1.75;">
        Click the button below to choose a new password. If you did not request this, you may safely ignore this email.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:999px;background-color:#701a33;">
                <a href="{{ $resetUrl }}" style="display:inline-block;padding:13px 22px;color:#ffffff;font-size:14px;font-weight:800;text-decoration:none;">
                    Reset Password
                </a>
            </td>
        </tr>
    </table>
@endsection
