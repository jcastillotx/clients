@php
    $title = $kind === 'overdue' ? 'Invoice overdue' : 'Invoice due soon';
@endphp
{{ $title }}

Invoice: {{ $invoice->invoice_number }}
Amount: ${{ number_format((float)$invoice->amount, 2) }}
Balance due: ${{ number_format((float)$invoice->balance_due, 2) }}
Due date: {{ $invoice->due_date?->format('Y-m-d') }}

View invoice:
{{ route('invoices.show', $invoice) }}

If you’ve already paid, thank you — please ignore this reminder.

