<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #0f172a; }
        .wrap { padding: 44px; }
        .topbar { border-left: 6px solid {{ $brand['accent_color'] ?? '#2563eb' }}; padding-left: 14px; margin-bottom: 24px; }
        .h1 { font-size: 26px; font-weight: 800; letter-spacing: 0.5px; }
        .muted { color: #64748b; }
        .grid { width: 100%; }
        .grid td { vertical-align: top; }
        .pill { display: inline-block; padding: 5px 10px; border-radius: 999px; font-weight: 700; font-size: 11px; }
        .pill-paid { background: #dcfce7; color: #166534; }
        .pill-overdue { background: #fee2e2; color: #991b1b; }
        .pill-open { background: #fffbeb; color: #92400e; }
        .box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin: 18px 0; }
        .items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items th { text-align: left; padding: 10px 8px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .items td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; }
        .right { text-align: right; }
        .totals { width: 100%; margin-top: 14px; }
        .totals td { padding: 6px 0; }
        .totals .grand td { padding-top: 10px; border-top: 2px solid #0f172a; font-weight: 800; font-size: 14px; }
        .footer { margin-top: 34px; font-size: 10px; color: #94a3b8; }
    </style>
</head>
<body>
@php
    $logoPath = public_path($brand['logo_path'] ?? 'images/logo.png');
    $logoDataUri = null;
    if (is_file($logoPath)) {
        $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
        $data = base64_encode(file_get_contents($logoPath));
        $logoDataUri = "data:image/{$ext};base64,{$data}";
    }
@endphp
<div class="wrap">
    <div class="topbar">
        <table class="grid">
            <tr>
                <td style="width: 60%;">
                    @if($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="Logo" style="width: 110px; height: auto; margin-bottom: 8px;">
                    @endif
                    <div class="h1">Invoice</div>
                    <div class="muted">{{ config('client-portal.invoice.company.name') }}</div>
                </td>
                <td class="right" style="width: 40%;">
                    <div style="font-size: 14px; font-weight: 700;">{{ $invoice->invoice_number }}</div>
                    <div class="muted">Issued {{ $invoice->issue_date->format('M d, Y') }}</div>
                    <div class="muted">Due {{ $invoice->due_date->format('M d, Y') }}</div>
                    <div style="margin-top: 10px;">
                        @if($invoice->isPaid())
                            <span class="pill pill-paid">PAID</span>
                        @elseif($invoice->isOverdue())
                            <span class="pill pill-overdue">OVERDUE</span>
                        @else
                            <span class="pill pill-open">OPEN</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div class="muted" style="font-weight: 700; text-transform: uppercase; font-size: 10px; letter-spacing: .08em;">Bill to</div>
        <div style="font-size: 14px; font-weight: 800; margin-top: 6px;">{{ $invoice->client->company_name }}</div>
        <div class="muted">{{ $invoice->client->contact_name }}</div>
        <div class="muted">{{ $invoice->client->email }}</div>
    </div>

    <table class="items">
        <thead>
        <tr>
            <th>Description</th>
            <th style="width: 70px;" class="right">Qty</th>
            <th style="width: 100px;" class="right">Unit</th>
            <th style="width: 110px;" class="right">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="right">{{ number_format($item->quantity, 2) }}</td>
                <td class="right">${{ number_format($item->unit_price, 2) }}</td>
                <td class="right">${{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="muted">Subtotal</td>
            <td class="right">${{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->tax_amount > 0)
            <tr>
                <td class="muted">Tax ({{ $invoice->tax_rate }}%)</td>
                <td class="right">${{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
        @endif
        @if($invoice->discount > 0)
            <tr>
                <td class="muted">Discount</td>
                <td class="right">-${{ number_format($invoice->discount, 2) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>Total</td>
            <td class="right">${{ number_format($invoice->amount, 2) }}</td>
        </tr>
        @if($invoice->balance_due > 0 && $invoice->balance_due < $invoice->amount)
            <tr>
                <td class="muted">Balance due</td>
                <td class="right">${{ number_format($invoice->balance_due, 2) }}</td>
            </tr>
        @endif
    </table>

    @if($invoice->notes)
        <div class="box">
            <div class="muted" style="font-weight: 700;">Notes</div>
            <div style="margin-top: 6px; white-space: pre-wrap;">{{ $invoice->notes }}</div>
        </div>
    @endif

    @if($invoice->terms)
        <div class="box">
            <div class="muted" style="font-weight: 700;">Terms</div>
            <div style="margin-top: 6px; white-space: pre-wrap;">{{ $invoice->terms }}</div>
        </div>
    @endif

    <div class="footer">
        {{ config('client-portal.invoice.company.name') }} · {{ config('client-portal.invoice.company.email') }}
    </div>
</div>
</body>
</html>

