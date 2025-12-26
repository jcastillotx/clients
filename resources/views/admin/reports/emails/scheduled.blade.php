<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <p>Attached is your scheduled <strong>{{ ucfirst($category) }}</strong> report.</p>

    @if(!empty($meta['start']) && !empty($meta['end']))
        <p>Date range: <strong>{{ $meta['start'] }}</strong> to <strong>{{ $meta['end'] }}</strong></p>
    @endif

    <p>This email was generated automatically by the admin reporting scheduler.</p>
</body>
</html>

