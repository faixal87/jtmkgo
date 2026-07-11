@extends('emails.layout')

@section('content')
    <h1 style="margin:0 0 16px; color:#1a1a1a; font-size:20px;">Test Email</h1>

    <p style="margin:0 0 12px; color:#4b5563; font-size:14px; line-height:1.6;">
        This is a test email sent from the JTMK Go! Mail Settings page.
    </p>

    <p style="margin:0; color:#4b5563; font-size:14px; line-height:1.6;">
        If you received this, the <strong>{{ strtoupper($providerLabel) }}</strong> mailer is configured correctly and email notifications will be delivered.
    </p>
@endsection
