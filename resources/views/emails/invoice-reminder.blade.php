@php
    $subject = $kind === 'overdue' ? 'Invoice overdue' : 'Invoice due soon';
    $preheader = 'Reminder about invoice ' . $invoice->invoice_number;
@endphp

@component('emails._layout', ['subject' => $subject, 'preheader' => $preheader])
    <div style="font-size:20px;font-weight:700;margin-bottom:12px;color:#0f172a;">
        @if($kind === 'overdue')
            Invoice overdue
        @else
            Invoice due soon
        @endif
    </div>

    <div style="font-size:14px;color:#334155;line-height:1.6;margin-bottom:16px;">
        This is a reminder about invoice <strong>{{ $invoice->invoice_number }}</strong>.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:16px;">
        <tr>
            <td style="padding:14px 16px;background:#f8fafc;">
                <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Invoice Details</div>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:13px;color:#334155;line-height:1.6;">
                    <strong>Amount:</strong> @money($invoice->amount)<br>
                    <strong>Balance due:</strong> @money($invoice->balance_due)<br>
                    <strong>Due date:</strong> {{ $invoice->due_date?->format('M d, Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div style="font-size:14px;color:#334155;line-height:1.6;margin-bottom:20px;">
        You can view and download your invoice from the client portal:
    </div>

    <div style="margin:18px 0;">
        <a href="{{ route('invoices.show', $invoice) }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:10px;font-weight:700;font-size:14px;">
            View invoice
        </a>
    </div>

    <div style="font-size:13px;color:#64748b;line-height:1.6;margin-top:24px;">
        If you've already paid, thank you — please ignore this reminder.
    </div>
@endcomponent

