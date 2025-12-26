Payment received

@php($invoice = $payment->invoice)
Invoice: {{ $invoice?->invoice_number ?? '—' }}
Amount: ${{ number_format((float) $payment->amount, 2) }}
Method: {{ ucfirst($payment->payment_method) }}
Status: {{ ucfirst($payment->status) }}
Date: {{ $payment->created_at?->format('M d, Y h:i A') }}

@if($invoice)
View invoice: {{ route('invoices.show', $invoice) }}
@endif

