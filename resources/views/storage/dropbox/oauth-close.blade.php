<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dropbox connected</title>
</head>
<body>
<script>
    (function () {
        try {
            if (window.opener && !window.opener.closed) {
                window.opener.postMessage({ type: 'dropbox_oauth_success' }, window.location.origin);
            }
        } catch (e) {}

        try { window.close(); } catch (e) {}

        // Fallback: if not a popup (or close blocked), redirect.
        window.location.href = @json($redirectTo ?? '/');
    })();
</script>
</body>
</html>

