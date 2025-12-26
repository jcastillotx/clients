@php
    $subject = 'Contract expiring soon';
    $preheader = 'Reminder: your contract is nearing expiration';
@endphp

@component('emails._layout', ['subject' => $subject, 'preheader' => $preheader])
    <div style="font-size:18px;font-weight:700;margin-bottom:8px;color:#0f172a;">
        Contract expiring soon
    </div>
    <div style="font-size:14px;color:#334155;line-height:1.6;margin-bottom:16px;">
        This is a friendly reminder that your contract is set to expire in {{ $daysRemaining }} days.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:14px 16px;background:#f8fafc;">
                <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Contract</div>
                <div style="font-size:14px;font-weight:700;color:#0f172a;">{{ $contract->title }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:13px;color:#334155;line-height:1.6;">
                    <strong>End date:</strong> {{ $contract->end_date?->format('M d, Y') ?? '—' }}<br>
                    <strong>Contract #:</strong> {{ $contract->contract_number }}
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top:18px;">
        <a href="{{ route('contracts.show', $contract) }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:13px;">
            View contract
        </a>
    </div>
@endcomponent

