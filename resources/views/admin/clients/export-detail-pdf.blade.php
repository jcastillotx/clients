<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        h2 { font-size: 14px; margin: 14px 0 6px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; font-size: 11px; }
    </style>
</head>
<body>
    <h1>Client Detail</h1>
    <div class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</div>
    <div class="muted">Client #{{ $client->id }}</div>

    <h2>Company</h2>
    <table>
        <tr><th>Company</th><td>{{ $client->company_name }}</td></tr>
        <tr><th>Contact</th><td>{{ $client->contact_name }}</td></tr>
        <tr><th>Email</th><td>{{ $client->email }}</td></tr>
        <tr><th>Phone</th><td>{{ $client->phone ?? '' }}</td></tr>
        <tr><th>Address</th><td>{{ $client->full_address }}</td></tr>
        <tr><th>Status</th><td>{{ $client->status }}</td></tr>
        <tr><th>Tier</th><td>{{ $client->tier }}</td></tr>
    </table>

    <h2>Summary</h2>
    <table>
        <tr><th>Open requests</th><td>{{ $stats['open_requests'] ?? 0 }}</td></tr>
        <tr><th>Total invoiced</th><td>${{ number_format((float) ($stats['invoices_total'] ?? 0), 2) }}</td></tr>
        <tr><th>Total paid</th><td>${{ number_format((float) ($stats['payments_total'] ?? 0), 2) }}</td></tr>
    </table>

    <h2>Linked Users</h2>
    <table>
        <thead>
        <tr><th>Name</th><th>Email</th><th>Active</th></tr>
        </thead>
        <tbody>
        @foreach($client->users as $u)
            <tr>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->is_active ? 'Yes' : 'No' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>

