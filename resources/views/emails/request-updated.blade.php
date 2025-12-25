@php
    $subject = 'Request updated';
    $preheader = 'There is an update to your request';
@endphp

@component('emails._layout', ['subject' => $subject, 'preheader' => $preheader])
    <div style="font-size:18px;font-weight:700;margin-bottom:8px;color:#0f172a;">
        Your request has been updated
    </div>
    <div style="font-size:14px;color:#334155;line-height:1.6;margin-bottom:16px;">
        We’ve updated your request. See the latest details in the portal.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:14px 16px;background:#f8fafc;">
                <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Request</div>
                <div style="font-size:14px;font-weight:700;color:#0f172a;">#{{ $request->id }} · {{ $request->title }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:13px;color:#334155;line-height:1.6;">
                    @if($oldStatus && $newStatus)
                        <strong>Status:</strong> {{ $oldStatus }} → {{ $newStatus }}<br>
                    @endif
                    <strong>Current status:</strong> {{ $request->status }}<br>
                    <strong>Priority:</strong> {{ $request->priority }}
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top:18px;">
        <a href="{{ route('requests.show', $request) }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:13px;">
            View request
        </a>
    </div>
@endcomponent

