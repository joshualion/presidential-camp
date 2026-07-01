<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f4f6f9;font-family:Arial,sans-serif;color:#222">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f9;padding:24px 12px">
    <tr><td align="center">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;background:#fff;border-radius:6px;overflow:hidden">
            <tr><td style="background:{{ $branding['color'] }};padding:22px;text-align:center;color:#fff">
                @if($branding['logo_url'])<img src="{{ $branding['logo_url'] }}" alt="{{ $branding['name'] }}" style="max-height:64px;max-width:240px">@else<h2 style="margin:0">{{ $branding['name'] }}</h2>@endif
            </td></tr>
            <tr><td style="padding:32px">
                <h1 style="font-size:24px;margin:0 0 20px;color:{{ $branding['color'] }}">{{ $campaign->subject }}</h1>
                <div style="font-size:16px;line-height:1.6;white-space:pre-line">{{ $campaign->body }}</div>
                @if($campaign->cta_label && $campaign->cta_url)
                    <p style="margin:28px 0 0"><a href="{{ $campaign->cta_url }}" style="display:inline-block;background:{{ $branding['color'] }};color:#fff;text-decoration:none;padding:12px 20px;border-radius:4px">{{ $campaign->cta_label }}</a></p>
                @endif
            </td></tr>
            <tr><td style="padding:18px 32px;background:#f7f7f7;color:#666;font-size:12px">Sent by {{ $branding['name'] }}.</td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
