<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; font-size: 11px; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>Client List</h1>
    <div class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</div>
    <br>

    <table>
        <thead>
            <tr>
                <th>Company</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Tier</th>
                <th>Status</th>
                <th class="right">Active Requests</th>
                <th class="right">Revenue</th>
                <th>Last Activity</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clients as $c)
                <tr>
                    <td>{{ $c->company_name }}</td>
                    <td>{{ $c->contact_name }}</td>
                    <td>{{ $c->email }}</td>
                    <td>{{ $c->tier }}</td>
                    <td>{{ $c->status }}</td>
                    <td class="right">{{ (int) ($c->active_requests_count ?? 0) }}</td>
                    <td class="right">${{ number_format((float) ($c->total_revenue ?? 0), 2) }}</td>
                    <td>{{ $c->last_activity_at ? \Illuminate\Support\Carbon::parse($c->last_activity_at)->format('Y-m-d H:i') : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

