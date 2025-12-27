<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Website Audit #{{ $audit->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#111; }
        h1,h2,h3 { margin: 0 0 8px 0; }
        .muted { color:#666; }
        .score { font-size: 28px; font-weight: bold; }
        .section { margin: 18px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Website Audit Report</h1>
    <p class="muted">
        Audit #{{ $audit->id }} · {{ $audit->website_url }}<br>
        Status: {{ $audit->status }} · Generated: {{ optional($audit->completed_at ?? $audit->created_at)->toDateTimeString() }}
    </p>

    <div class="section">
        <h2>Executive summary</h2>
        <p>
            Overall score:
            <span class="score">{{ data_get($audit->scores, 'overall', $audit->score ?? '—') }}</span>
        </p>
        @if(is_array(data_get($report, 'ai')) && !empty(data_get($report, 'ai.summary')))
            <p>{{ data_get($report, 'ai.summary') }}</p>
        @endif
    </div>

    <div class="section">
        <h2>Scores</h2>
        <table>
            <tr>
                <th>SEO</th>
                <th>Performance</th>
                <th>Accessibility</th>
                <th>Security</th>
                <th>Mobile</th>
            </tr>
            <tr>
                <td>{{ data_get($audit->scores, 'seo', '—') }}</td>
                <td>{{ data_get($audit->scores, 'performance', '—') }}</td>
                <td>{{ data_get($audit->scores, 'accessibility', '—') }}</td>
                <td>{{ data_get($audit->scores, 'security', '—') }}</td>
                <td>{{ data_get($audit->scores, 'mobile', '—') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Prioritized issues</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Severity</th>
                    <th style="width: 110px;">Category</th>
                    <th style="width: 160px;">Type</th>
                    <th>Description</th>
                    <th style="width: 220px;">Affected</th>
                </tr>
            </thead>
            <tbody>
                @foreach($audit->issues()->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'error' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")->limit(50)->get() as $i)
                    <tr>
                        <td>{{ $i->severity }}</td>
                        <td>{{ $i->category }}</td>
                        <td>{{ $i->issue_type }}</td>
                        <td>{{ $i->description }}</td>
                        <td>{{ $i->affected_url }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Pages (sample)</h2>
        <table>
            <thead>
                <tr>
                    <th>URL</th>
                    <th>Status</th>
                    <th>Title</th>
                    <th>Load ms</th>
                    <th>Size KB</th>
                </tr>
            </thead>
            <tbody>
                @foreach($audit->pages()->orderByDesc('status_code')->limit(25)->get() as $p)
                    <tr>
                        <td>{{ $p->url }}</td>
                        <td>{{ $p->status_code }}</td>
                        <td>{{ $p->title }}</td>
                        <td>{{ $p->load_time_ms }}</td>
                        <td>{{ $p->page_size_kb }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(is_array(data_get($report, 'ai')) && is_array(data_get($report, 'ai.recommendations')))
        <div class="section">
            <h2>Recommended action plan (AI)</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 90px;">Priority</th>
                        <th>Title</th>
                        <th>Why</th>
                        <th>How</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach((array) data_get($report, 'ai.recommendations', []) as $r)
                        <tr>
                            <td>{{ data_get($r, 'priority') }}</td>
                            <td>{{ data_get($r, 'title') }}</td>
                            <td>{{ data_get($r, 'why') }}</td>
                            <td>{{ data_get($r, 'how') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</body>
</html>

