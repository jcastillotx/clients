@php
    $invoice = $payment->invoice;
    $subject = 'Payment received';
    $preheader = 'Your payment receipt is ready';
@endphp

@component('emails._layout', ['subject' => $subject, 'preheader' => $preheader])
    <div style="font-size:18px;font-weight:700;margin-bottom:8px;color:#0f172a;">
        Payment received
    </div>
    <div style="font-size:14px;color:#334155;line-height:1.6;margin-bottom:16px;">
        Thank you — we’ve received your payment.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:14px 16px;background:#f8fafc;">
                <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Receipt</div>
                <div style="font-size:14px;font-weight:700;color:#0f172a;">
                    @if($invoice) {{ $invoice->invoice_number }} @else Invoice payment @endif
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:13px;color:#334155;line-height:1.6;">
                    <strong>Amount:</strong> @money($payment->amount)<br>
                    <strong>Method:</strong> {{ ucfirst($payment->payment_method) }}<br>
                    <strong>Status:</strong> {{ ucfirst($payment->status) }}<br>
                    <strong>Date:</strong> {{ $payment->created_at?->format('M d, Y h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    @if($invoice)
        <div style="margin-top:18px;">
            <a href="{{ route('invoices.show', $invoice) }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:13px;">
                View invoice
            </a>
        </div>
    @endif
@endcomponent

