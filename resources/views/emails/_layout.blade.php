@php
    $companyName = config('client-portal.invoice.company.name', config('app.name'));
    $supportEmail = config('client-portal.support_email');
    $appUrl = config('app.url');

    $logoPath = public_path('images/logo.png');
    $logoDataUri = null;
    if (is_file($logoPath)) {
        $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
        $data = base64_encode(file_get_contents($logoPath));
        $logoDataUri = "data:image/{$ext};base64,{$data}";
    }
@endphp

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? $companyName }}</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;background:#ffffff;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    @if($logoDataUri)
                                        <img src="{{ $logoDataUri }}" alt="{{ $companyName }}" style="height:32px;max-height:32px;display:block;">
                                    @else
                                        <div style="font-size:16px;font-weight:700;color:#0f172a;">{{ $companyName }}</div>
                                    @endif
                                </td>
                                <td align="right" style="font-size:12px;color:#64748b;">
                                    {{ $preheader ?? '' }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px;">
                        {{ $slot }}
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px 24px;border-top:1px solid #e2e8f0;background:#f8fafc;">
                        <div style="font-size:12px;color:#64748b;line-height:1.5;">
                            Need help? Contact us at <a href="mailto:{{ $supportEmail }}" style="color:#0f172a;text-decoration:underline;">{{ $supportEmail }}</a>.
                            <br>
                            <a href="{{ $appUrl }}" style="color:#0f172a;text-decoration:underline;">{{ $appUrl }}</a>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

