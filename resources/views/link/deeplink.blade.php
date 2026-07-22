<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Opening app…') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f8fafc; color: #0f172a; margin: 0;
            min-height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center;
        }
        @media (prefers-color-scheme: dark) { body { background: #0f172a; color: #f1f5f9; } }
        .box { padding: 24px; }
        .spinner {
            width: 40px; height: 40px; margin: 0 auto 16px;
            border: 3px solid rgba(99, 102, 241, .25); border-top-color: #6366f1;
            border-radius: 50%; animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        h1 { font-size: 18px; margin: 0 0 6px; }
        p { margin: 0 0 18px; font-size: 14px; color: #64748b; }
        a.btn {
            display: inline-block; min-height: 44px; line-height: 44px; padding: 0 22px;
            background: #4f46e5; color: #fff; border-radius: 10px; text-decoration: none;
            font-size: 14px; font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner" aria-hidden="true"></div>
        <h1>{{ __('Opening app…') }}</h1>
        <p>{{ __("If nothing happens, you'll be taken to the website automatically.") }}</p>
        <a class="btn" href="{{ $fallback }}">{{ __('Continue in browser') }}</a>
    </div>
    <script>
        (function () {
            var fallback = @json($fallback);
            try { window.location.href = @json($uri); } catch (e) {}
            setTimeout(function () { window.location.replace(fallback); }, 1600);
        })();
    </script>
</body>
</html>
