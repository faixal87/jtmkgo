@extends('emails.layout')

@section('content')
    <div style="display:inline-block;margin-bottom:16px;border-radius:999px;background:#f9edf1;color:#701a33;font-size:11.5px;font-weight:800;letter-spacing:.8px;padding:8px 14px;text-transform:uppercase;">Mail Settings</div>
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;color:#2a1a1f;font-size:23px;line-height:1.35;font-weight:700;">Test Email</h1>

    <p style="margin:0 0 12px;color:#5c4b50;font-size:15px;line-height:1.75;">
        This is a test email sent from the JTMK Go! Mail Settings page.
    </p>

    <p style="margin:0;color:#5c4b50;font-size:15px;line-height:1.75;">
        If you received this, the <strong>{{ strtoupper($providerLabel) }}</strong> mailer is configured correctly and email notifications will be delivered.
    </p>
@endsection
