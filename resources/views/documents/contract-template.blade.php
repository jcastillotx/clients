<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contract - {{ $contract->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 16px; }
        .brand { font-size: 16px; font-weight: 700; }
        .muted { color: #6B7280; }
        h1 { font-size: 18px; margin: 0 0 6px 0; }
        h2 { font-size: 14px; margin: 18px 0 8px 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ $appName }}</div>
        <div class="muted">Contract Draft</div>
    </div>

    <h1>{{ $contract->title }}</h1>
    <div class="muted">
        Client: <strong>{{ $client->company_name }}</strong> ·
        Contract #: <strong>{{ $contract->contract_number }}</strong> ·
        Generated: <strong>{{ $generatedAt->format('Y-m-d') }}</strong>
    </div>

    <hr>

    {!! $html !!}

    <div class="muted" style="margin-top: 18px;">
        This contract is provided for review and signature in the client portal.
    </div>
</body>
</html>

