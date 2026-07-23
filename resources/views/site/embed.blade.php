<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($link->title ?: $link->shortUrl()) }} — {{ site_name() }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 16px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc; color: #0f172a;
        }
        .card {
            max-width: 380px; margin: 0 auto; background: #fff;
            border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
        }
        .title { font-weight: 600; font-size: 16px; margin: 0 0 6px; word-break: break-word; }
        .url { font-size: 13px; color: #6366f1; text-decoration: none; word-break: break-all; }
        .count { margin-top: 14px; font-size: 28px; font-weight: 700; }
        .count small { font-size: 13px; font-weight: 500; color: #64748b; }
        .footer { margin-top: 16px; font-size: 11px; }
        .footer a { color: #94a3b8; text-decoration: none; }
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #e2e8f0; }
            .card { background: #1e293b; border-color: #334155; }
            .count small { color: #94a3b8; }
        }
    </style>
</head>
<body>
    <div class="card">
        <p class="title">{{ $link->title ?: $link->shortUrl() }}</p>
        <a class="url" href="{{ $link->shortUrl() }}" target="_blank" rel="noopener">{{ $link->shortUrl() }}</a>
        <div class="count">{{ format_number($link->clicks_count) }} <small>{{ __('clicks') }}</small></div>
        @unless($link->user && $link->user->currentPlan()->hasFeature('remove_branding'))
            <div class="footer">
                <a href="{{ url('/?ref=embed') }}" target="_blank" rel="noopener">⚡ {{ __('Powered by :name', ['name' => site_name()]) }}</a>
            </div>
        @endunless
    </div>
</body>
</html>
