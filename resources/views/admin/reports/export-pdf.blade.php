<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Report' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .muted { color: #6b7280; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        .meta { margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
<h1>{{ $title ?? 'Report' }}</h1>
<div class="meta muted">Range: {{ $from ?? '—' }} → {{ $to ?? '—' }}</div>

<table>
    <thead>
    <tr>
        @foreach(($headings ?? []) as $h)
            <th>{{ $h }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach(($rows ?? []) as $row)
        <tr>
            @foreach($row as $cell)
                <td>{{ is_scalar($cell) ? $cell : json_encode($cell) }}</td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>

