<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        h2 { font-size: 14px; margin: 16px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin: 6px 0 14px; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #f5f5f5; }
        .muted { color: #666; font-size: 11px; }
    </style>
</head>
<body>
    <h1>Admin Report: {{ ucfirst($category) }}</h1>
    <div class="muted">
        Generated at {{ now()->toDateTimeString() }}
        @if(!empty($payload['meta']['start']) && !empty($payload['meta']['end']))
            • Date range {{ $payload['meta']['start'] }} to {{ $payload['meta']['end'] }}
        @endif
    </div>

    @foreach(($payload['tables'] ?? []) as $name => $rows)
        <h2>{{ $name }}</h2>
        @php
            $rows = is_array($rows) ? $rows : [];
            $first = $rows[0] ?? null;
            $headings = is_array($first) ? array_keys($first) : [];
        @endphp
        @if(empty($rows))
            <div class="muted">(no data)</div>
        @else
            <table>
                <thead>
                    <tr>
                        @foreach($headings as $h)
                            <th>{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            @foreach($headings as $h)
                                <td>{{ is_array($row) ? ($row[$h] ?? '') : '' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
</body>
</html>

