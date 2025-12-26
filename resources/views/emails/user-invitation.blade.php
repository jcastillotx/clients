@extends('emails._layout')

@section('content')
    <h1 style="margin:0 0 12px 0;font-size:20px;line-height:1.2;color:#0f172a;">
        You’ve been invited
    </h1>

    <p style="margin:0 0 10px 0;color:#334155;line-height:1.6;">
        An account has been created for <strong>{{ $user->email }}</strong> with the role <strong>{{ $roleLabel }}</strong>.
    </p>

    <p style="margin:0 0 14px 0;color:#334155;line-height:1.6;">
        Set your password to access the portal:
    </p>

    <p style="margin:0 0 18px 0;">
        <a href="{{ $setPasswordUrl }}" style="display:inline-block;background:#0f172a;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;">
            Set password
        </a>
    </p>

    <p style="margin:0;color:#64748b;line-height:1.6;">
        If you weren’t expecting this invitation, you can ignore this email.
    </p>
@endsection

