@php
    $subject = 'You\'ve been invited';
    $preheader = 'Set up your account to access the portal';
@endphp

@component('emails._layout', ['subject' => $subject, 'preheader' => $preheader])
    <div style="font-size:20px;font-weight:700;margin-bottom:12px;color:#0f172a;">
        You've been invited
    </div>

    <div style="font-size:14px;color:#334155;line-height:1.6;margin-bottom:12px;">
        An account has been created for <strong>{{ $user->email }}</strong> with the role <strong>{{ $roleLabel }}</strong>.
    </div>

    <div style="font-size:14px;color:#334155;line-height:1.6;margin-bottom:20px;">
        Set your password to access the portal:
    </div>

    <div style="margin:18px 0;">
        <a href="{{ $setPasswordUrl }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:10px;font-weight:700;font-size:14px;">
            Set password
        </a>
    </div>

    <div style="font-size:13px;color:#64748b;line-height:1.6;margin-top:24px;">
        If you weren't expecting this invitation, you can ignore this email.
    </div>
@endcomponent

