Welcome, {{ $user->name }}

Your client portal account has been created.

Portal: {{ $portalUrl }}

@if($passwordSetLinkSent)
We’ve sent a separate email with a secure link to set your password.
@elseif($temporaryPassword)
Temporary password: {{ $temporaryPassword }}
Please log in and change your password immediately.
@endif

