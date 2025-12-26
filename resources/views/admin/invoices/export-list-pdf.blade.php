<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoices Export</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #0f172a; }
        .h { font-size: 18px; font-weight: 800; margin-bottom: 6px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 8px 6px; border-bottom: 1px solid #e2e8f0; }
        th { text-align: left; background: #f8fafc; }
        .right { text-align: right; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; }
        .footer { margin-top: 18px; font-size: 10px; color: #94a3b8; }
    </style>
</head>
<body>
@php
    $brand = $brand ?? [];
@endphp
<div class="h">Invoices Export</div>
<div class="muted">{{ config('client-portal.invoice.company.name') }} · Generated {{ now()->format('Y-m-d H:i') }}</div>

<table>
    <thead>
    <tr>
        <th>Invoice #</th>
        <th>Client</th>
        <th>Issue</th>
        <th>Due</th>
        <th>Status</th>
        <th class="right">Amount</th>
        <th class="right">Paid</th>
        <th class="right">Balance</th>
    </tr>
    </thead>
    <tbody>
    @foreach($invoices as $inv)
        <tr>
            <td>{{ $inv->invoice_number }}</td>
            <td>{{ $inv->client?->company_name }}</td>
            <td>{{ $inv->issue_date?->format('Y-m-d') }}</td>
            <td>{{ $inv->due_date?->format('Y-m-d') }}</td>
            <td>{{ $inv->status }}</td>
            <td class="right">${{ number_format((float)$inv->amount, 2) }}</td>
            <td class="right">${{ number_format((float)$inv->total_paid, 2) }}</td>
            <td class="right">${{ number_format((float)$inv->balance_due, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">Export limited for performance.</div>
</body>
</html>

