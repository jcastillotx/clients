New invoice available

Invoice: {{ $invoice->invoice_number }}
Issue date: {{ $invoice->issue_date?->format('M d, Y') }}
Due date: {{ $invoice->due_date?->format('M d, Y') }}
Total: ${{ number_format((float) $invoice->amount, 2) }}

View invoice: {{ route('invoices.show', $invoice) }}

