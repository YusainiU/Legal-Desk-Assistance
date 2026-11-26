<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $correspondenceSubject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;color:#243247;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9;">
<tr><td align="center" style="padding:28px 12px;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background-color:#ffffff;border:1px solid #dce3eb;">
        <tr><td style="padding:28px 32px;background-color:#172b45;color:#ffffff;border-bottom:4px solid #b49a68;">
            <div style="font-size:12px;letter-spacing:2px;color:#dce3eb;text-transform:uppercase;">Correspondence</div>
            <div style="font-size:24px;font-weight:bold;line-height:1.4;margin-top:8px;">{{ $firmName }}</div>
        </td></tr>
        <tr><td style="padding:32px;">
            <h1 style="font-size:20px;line-height:1.4;margin:0 0 24px;color:#172b45;overflow-wrap:anywhere;">{{ $correspondenceSubject }}</h1>
            <div style="font-size:15px;line-height:1.75;overflow-wrap:anywhere;">{!! nl2br(e($correspondenceBody)) !!}</div>
        </td></tr>
        <tr><td style="padding:22px 32px;border-top:1px solid #dce3eb;background-color:#f8fafc;">
            <div style="font-size:13px;font-weight:bold;color:#172b45;">{{ $firmName }}</div>
            <p style="font-size:12px;line-height:1.6;color:#526174;margin:12px 0 0;">This correspondence is intended for the named recipient and may contain confidential information. If you received it in error, please notify the sender and delete it.</p>
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
