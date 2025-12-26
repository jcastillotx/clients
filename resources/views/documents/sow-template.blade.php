<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>SOW - {{ $request->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 16px; }
        .brand { font-size: 16px; font-weight: 700; }
        .muted { color: #6B7280; }
        h1 { font-size: 18px; margin: 0 0 6px 0; }
        h2 { font-size: 14px; margin: 18px 0 8px 0; border-bottom: 1px solid #E5E7EB; padding-bottom: 4px; }
        .box { border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #E5E7EB; padding: 6px 0; vertical-align: top; }
        th { text-align: left; color: #374151; font-weight: 700; }
        .right { text-align: right; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #F3F4F6; color: #374151; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ $appName }}</div>
        <div class="muted">Statement of Work (SOW)</div>
    </div>

    <h1>{{ $request->title }}</h1>
    <div class="muted">
        Client: <strong>{{ $client->company_name }}</strong> ·
        Request #: <strong>{{ $request->id }}</strong> ·
        SOW #: <strong>{{ $contract->contract_number }}</strong> ·
        Generated: <strong>{{ $generatedAt->format('Y-m-d') }}</strong>
    </div>

    <h2>Executive Summary</h2>
    <div class="box">
        {{ $sections['executive_summary'] ?? 'This SOW outlines objectives, scope, timeline, and investment for the requested work.' }}
    </div>

    <h2>Scope</h2>
    <div class="box">
        {{ $sections['scope_overview'] ?? ($request->description ?: 'Scope to be confirmed.') }}
    </div>

    <h2>Deliverables</h2>
    <div class="box">
        <ul style="margin: 0; padding-left: 18px;">
            @foreach(($estimate['tasks'] ?? []) as $t)
                @if(is_array($t) && !empty($t['name']))
                    <li>{{ $t['name'] }}</li>
                @endif
            @endforeach
        </ul>
    </div>

    <h2>Timeline</h2>
    <div class="box">
        {{ $sections['timeline_overview'] ?? 'Timeline will be finalized after kickoff. Below is an estimate based on the current information.' }}
        @php $timeline = (array) ($estimate['timeline'] ?? []); @endphp
        <div style="margin-top: 8px;">
            <span class="badge">Weeks (L/M/H): {{ $timeline['duration_weeks_low'] ?? '—' }}/{{ $timeline['duration_weeks_mid'] ?? '—' }}/{{ $timeline['duration_weeks_high'] ?? '—' }}</span>
        </div>
        @if(!empty($timeline['milestones']) && is_array($timeline['milestones']))
            <ul style="margin: 8px 0 0 0; padding-left: 18px;">
                @foreach($timeline['milestones'] as $m)
                    <li>{{ $m }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <h2>Investment</h2>
    <div class="box">
        {{ $sections['investment_overview'] ?? 'Investment is based on estimated effort, rate, markup, and contingency.' }}
        @php $totals = (array) (($pricing['totals'] ?? []) ?: []); @endphp
        <table style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Scenario</th>
                    <th class="right">Hours</th>
                    <th class="right">Subtotal</th>
                    <th class="right">Markup</th>
                    <th class="right">Contingency</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['low' => 'Low', 'mid' => 'Mid', 'high' => 'High'] as $k => $label)
                    @php $row = (array) ($totals[$k] ?? []); @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="right">{{ number_format((float)($row['hours'] ?? 0), 1) }}</td>
                        <td class="right">${{ number_format((float)($row['subtotal'] ?? 0), 2) }}</td>
                        <td class="right">${{ number_format((float)($row['markup'] ?? 0), 2) }}</td>
                        <td class="right">${{ number_format((float)($row['contingency'] ?? 0), 2) }}</td>
                        <td class="right"><strong>${{ number_format((float)($row['total'] ?? 0), 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2>Terms</h2>
    <div class="box">
        {{ $sections['terms_overview'] ?? 'Work begins upon approval and signature. Any changes to scope may require a change order. Payment terms will be invoiced per agreed schedule.' }}
    </div>

    <div class="muted" style="margin-top: 18px;">
        This SOW is intended for review and approval. Signature is captured electronically in the client portal and recorded with timestamp and IP address.
    </div>
</body>
</html>

