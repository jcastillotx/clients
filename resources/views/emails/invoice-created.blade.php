@php
    $subject = 'New invoice available';
    $preheader = 'A new invoice has been generated for your account';
@endphp

@component('emails._layout', ['subject' => $subject, 'preheader' => $preheader])
    <div style="font-size:18px;font-weight:700;margin-bottom:8px;color:#0f172a;">
        New invoice available
    </div>
    <div style="font-size:14px;color:#334155;line-height:1.6;margin-bottom:16px;">
        Your new invoice is ready. You can review the details and pay securely in the portal.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:14px 16px;background:#f8fafc;">
                <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Invoice</div>
                <div style="font-size:14px;font-weight:700;color:#0f172a;">{{ $invoice->invoice_number }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:13px;color:#334155;line-height:1.6;">
                    <strong>Issue date:</strong> {{ $invoice->issue_date?->format('M d, Y') }}<br>
                    <strong>Due date:</strong> {{ $invoice->due_date?->format('M d, Y') }}<br>
                    <strong>Total:</strong> @money($invoice->amount)
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top:18px;">
        <a href="{{ route('invoices.show', $invoice) }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:13px;">
            View invoice
        </a>
    </div>
@endcomponent

