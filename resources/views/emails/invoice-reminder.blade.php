@extends('emails._layout')

@section('content')
    <h1 style="margin:0 0 12px 0;font-size:20px;line-height:1.2;color:#0f172a;">
        @if($kind === 'overdue')
            Invoice overdue
        @else
            Invoice due soon
        @endif
    </h1>

    <p style="margin:0 0 10px 0;color:#334155;line-height:1.6;">
        This is a reminder about invoice <strong>{{ $invoice->invoice_number }}</strong>.
    </p>

    <p style="margin:0 0 10px 0;color:#334155;line-height:1.6;">
        <strong>Amount:</strong> @money($invoice->amount)<br>
        <strong>Balance due:</strong> @money($invoice->balance_due)<br>
        <strong>Due date:</strong> {{ $invoice->due_date?->format('M d, Y') }}
    </p>

    <p style="margin:0 0 14px 0;color:#334155;line-height:1.6;">
        You can view and download your invoice from the client portal:
    </p>

    <p style="margin:0 0 18px 0;">
        <a href="{{ route('invoices.show', $invoice) }}" style="display:inline-block;background:#0f172a;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;">
            View invoice
        </a>
    </p>

    <p style="margin:0;color:#64748b;line-height:1.6;">
        If you’ve already paid, thank you — please ignore this reminder.
    </p>
@endsection

