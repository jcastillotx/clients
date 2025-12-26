@php
    $subject = 'Welcome to ' . config('app.name');
    $preheader = 'Your client portal account is ready';
@endphp

@component('emails._layout', ['subject' => $subject, 'preheader' => $preheader])
    <div style="font-size:18px;font-weight:700;margin-bottom:8px;color:#0f172a;">
        Welcome, {{ $user->name }}
    </div>
    <div style="font-size:14px;color:#334155;line-height:1.6;margin-bottom:16px;">
        Your client portal account has been created. You can sign in to view requests, invoices, contracts, and documents.
    </div>

    <div style="margin:18px 0;">
        <a href="{{ $portalUrl }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:13px;">
            Open client portal
        </a>
    </div>

    @if($passwordSetLinkSent)
        <div style="font-size:13px;color:#334155;line-height:1.6;">
            We’ve sent a separate email with a secure link to set your password.
        </div>
    @elseif($temporaryPassword)
        <div style="font-size:13px;color:#334155;line-height:1.6;">
            <strong>Temporary password:</strong> {{ $temporaryPassword }}<br>
            Please log in and change your password immediately.
        </div>
    @endif
@endcomponent

