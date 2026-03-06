<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Google Login</title>
</head>
<body>
<script>
    (function () {
        const payload = {
            type: @json($success ? 'google-auth-success' : 'google-auth-failed'),
            redirect_url: @json($redirectUrl ?? null),
        };

        if (window.opener && !window.opener.closed) {
            window.opener.postMessage(payload, window.location.origin);
            window.close();
            return;
        }

        if (payload.redirect_url) {
            window.location.href = payload.redirect_url;
            return;
        }

        window.location.href = '/login';
    })();
</script>
</body>
</html>
