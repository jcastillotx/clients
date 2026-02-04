@php
    $subject = 'Your scheduled report is ready';
    $preheader = $title ?? 'View your report';
@endphp

@component('emails._layout', ['subject' => $subject, 'preheader' => $preheader])
    <div style="font-size:18px;font-weight:700;margin-bottom:12px;color:#0f172a;">
        Your scheduled report is ready
    </div>

    <div style="font-size:14px;color:#334155;line-height:1.6;margin-bottom:16px;">
        Here is your scheduled report: <strong>{{ $title }}</strong>
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:16px;">
        <tr>
            <td style="padding:14px 16px;background:#f8fafc;">
                <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Report Period</div>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:13px;color:#334155;line-height:1.6;">
                    <strong>Date range:</strong> {{ $from }} → {{ $to }}
                </div>
            </td>
        </tr>
    </table>

    <div style="font-size:14px;color:#334155;line-height:1.6;">
        The report is attached to this email.
    </div>
@endcomponent

