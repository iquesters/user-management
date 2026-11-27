{{-- The sign-in code email when iquesters/notifications isn't installed (with it, its editable "Sign-in code" template is used). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2328;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 12px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:12px;overflow:hidden;">
            <tr><td style="padding:20px 24px;border-bottom:1px solid #eceef1;">
                @if ($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $app }}" height="36" style="height:36px;max-width:180px;vertical-align:middle;margin-right:10px;border-radius:6px;">@endif
                <span style="font-weight:600;font-size:16px;vertical-align:middle;">{{ $app }}</span>
            </td></tr>
            <tr><td style="padding:24px;font-size:15px;line-height:1.6;">
                <p style="margin:0 0 14px;">Hi there,</p>
                <p style="margin:0 0 14px;">Here's your code to sign in to <strong>{{ $app }}</strong>:</p>
                <p style="margin:6px 0 18px;"><span style="display:inline-block;padding:12px 22px;border-radius:10px;background:#f1f3f7;font-size:30px;font-weight:700;letter-spacing:8px;font-family:Consolas,Menlo,monospace;color:#111827;">{{ $code }}</span></p>
                <p style="margin:0 0 14px;">Just enter it on the sign-in page. It works for the next <strong>{{ $minutes }} minutes</strong> and only once.</p>
                <p style="margin:0 0 14px;"><strong>Didn't try to sign in?</strong> No need to worry: simply ignore this email. Nobody can get into your account without this code.</p>
                <p style="margin:0;">See you soon,<br>The {{ $app }} team</p>
            </td></tr>
            <tr><td style="padding:16px 24px;border-top:1px solid #eceef1;font-size:12px;color:#6b7280;">Sent by {{ $app }}.
                <div style="margin-top:6px;"><a href="https://iquesters.com" style="color:#6b7280;text-decoration:underline;">Platform developed by Iquesters Solutions LLP</a></div>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
