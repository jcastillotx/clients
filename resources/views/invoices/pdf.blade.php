<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .container {
            padding: 40px;
        }
        .header {
            margin-bottom: 28px;
        }
        .header-table {
            width: 100%;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-info {
            float: left;
        }
        .invoice-info {
            float: right;
            text-align: right;
        }
        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #0f172a;
        }
        .invoice-number {
            font-size: 14px;
            color: #666;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .billing-info {
            margin-bottom: 30px;
        }
        .billing-info h3 {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .billing-info p {
            margin: 0;
        }
        .dates {
            margin-bottom: 30px;
        }
        .dates table {
            width: 300px;
        }
        .dates td {
            padding: 5px 0;
        }
        .dates td:last-child {
            text-align: right;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: 600;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .totals {
            float: right;
            width: 300px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 8px 0;
        }
        .totals td:last-child {
            text-align: right;
        }
        .totals .total-row {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 16px;
        }
        .notes {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .notes h4 {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .footer {
            margin-top: 60px;
            text-align: center;
            color: #999;
            font-size: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 3px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
        }
        .status-paid {
            background: #28a745;
            color: white;
        }
        .status-unpaid {
            background: #f59e0b;
            color: #111827;
        }
        .status-overdue {
            background: #dc2626;
            color: white;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/logo.png');
        $logoDataUri = null;
        if (is_file($logoPath)) {
            $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = base64_encode(file_get_contents($logoPath));
            $logoDataUri = "data:image/{$ext};base64,{$data}";
        }
    @endphp
    <div class="container">
        <div class="header clearfix">
            <table class="header-table">
                <tr>
                    <td style="width: 60%;">
                        @if($logoDataUri)
                            <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
                        @endif
                        <div class="company-name">{{ config('client-portal.invoice.company.name') }}</div>
                        <p>{{ config('client-portal.invoice.company.address') }}</p>
                        <p>{{ config('client-portal.invoice.company.email') }}</p>
                        <p>{{ config('client-portal.invoice.company.phone') }}</p>
                    </td>
                    <td style="width: 40%; text-align: right;">
                        <div class="invoice-title">INVOICE</div>
                        <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                        <div style="margin-top: 10px;">
                            @if($invoice->isPaid())
                                <span class="status-badge status-paid">PAID</span>
                            @elseif($invoice->isOverdue())
                                <span class="status-badge status-overdue">OVERDUE</span>
                            @else
                                <span class="status-badge status-unpaid">UNPAID</span>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="billing-info clearfix">
            <div style="float: left; width: 50%;">
                <h3>Bill To</h3>
                <p><strong>{{ $invoice->client->company_name }}</strong></p>
                <p>{{ $invoice->client->contact_name }}</p>
                @if($invoice->client->address)
                <p>{{ $invoice->client->address }}</p>
                @endif
                @if($invoice->client->city || $invoice->client->state || $invoice->client->zip_code)
                <p>{{ $invoice->client->city }}, {{ $invoice->client->state }} {{ $invoice->client->zip_code }}</p>
                @endif
                <p>{{ $invoice->client->email }}</p>
            </div>
            <div class="dates" style="float: right;">
                <table>
                    <tr>
                        <td><strong>Issue Date:</strong></td>
                        <td>{{ $invoice->issue_date->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Due Date:</strong></td>
                        <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                    </tr>
                    @if($invoice->paid_at)
                    <tr>
                        <td><strong>Paid Date:</strong></td>
                        <td>{{ $invoice->paid_at->format('M d, Y') }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div style="clear: both;"></div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center" style="width: 80px;">Qty</th>
                    <th class="text-right" style="width: 100px;">Unit Price</th>
                    <th class="text-right" style="width: 100px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td>${{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->tax_amount > 0)
                <tr>
                    <td>Tax ({{ $invoice->tax_rate }}%)</td>
                    <td>${{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->discount > 0)
                <tr>
                    <td>Discount</td>
                    <td>-${{ number_format($invoice->discount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total</td>
                    <td>${{ number_format($invoice->amount, 2) }}</td>
                </tr>
                @if($invoice->balance_due > 0 && $invoice->balance_due < $invoice->amount)
                <tr>
                    <td>Balance Due</td>
                    <td>${{ number_format($invoice->balance_due, 2) }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div style="clear: both;"></div>

        @if($invoice->notes)
        <div class="notes">
            <h4>Notes</h4>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif

        @if($invoice->terms)
        <div class="notes">
            <h4>Terms & Conditions</h4>
            <p>{{ $invoice->terms }}</p>
        </div>
        @endif

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>{{ config('client-portal.invoice.company.name') }} | {{ config('client-portal.invoice.company.email') }}</p>
        </div>
    </div>
</body>
</html>
