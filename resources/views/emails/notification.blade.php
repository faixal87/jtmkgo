@extends('emails.layout')

@section('content')
    @if ($isAnnouncement ?? false)
        <p style="margin:0 0 12px; color:#b45309; font-size:12px; font-weight:bold; letter-spacing:0.05em; text-transform:uppercase;">Announcement</p>
    @endif

    <h1 style="margin:0 0 16px; color:#1a1a1a; font-size:20px;">{{ $title }}</h1>

    <p style="margin:0 0 24px; color:#4b5563; font-size:14px; line-height:1.6; white-space:pre-line;">{{ $body }}</p>

    @if ($actionUrl)
        <table role="presentation" cellpadding="0" cellspacing="0">
            <tr>
                <td style="border-radius:8px; background-color:#29231f;">
                    <a href="{{ $actionUrl }}" style="display:inline-block; padding:12px 20px; color:#fbbf24; font-size:14px; font-weight:bold; text-decoration:none;">
                        {{ $actionLabel ?: 'View Details' }}
                    </a>
                </td>
            </tr>
        </table>
    @endif
@endsection
