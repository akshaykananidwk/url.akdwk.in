<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>@yield('title', __('Installer')) — {{ config('app.name', 'Shortl') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f1f5f9; color: #0f172a; line-height: 1.55;
            min-height: 100vh; display: flex; flex-direction: column; align-items: center;
            padding: 32px 16px;
        }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 20px; margin-bottom: 24px; }
        .brand-mark { width: 38px; height: 38px; border-radius: 10px; background: #4f46e5; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .steps { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px 14px; margin-bottom: 24px; font-size: 13px; color: #64748b; }
        .step { display: flex; align-items: center; gap: 6px; }
        .step .dot { width: 22px; height: 22px; border-radius: 50%; background: #e2e8f0; color: #64748b; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .step.current { color: #4f46e5; font-weight: 600; }
        .step.current .dot { background: #4f46e5; color: #fff; }
        .step.done .dot { background: #22c55e; color: #fff; }
        .card { width: 100%; max-width: 620px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; box-shadow: 0 1px 3px rgba(15, 23, 42, .06); }
        h1 { font-size: 22px; margin-bottom: 6px; }
        .muted { color: #64748b; font-size: 14px; }
        .list { list-style: none; margin: 16px 0; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .list li { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 14px; font-size: 14px; border-top: 1px solid #f1f5f9; }
        .list li:first-child { border-top: 0; }
        .ok { color: #16a34a; font-weight: 600; }
        .fail { color: #dc2626; font-weight: 600; }
        .dot-ok, .dot-fail { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 8px; }
        .dot-ok { background: #22c55e; }
        .dot-fail { background: #ef4444; }
        label { display: block; font-size: 13px; font-weight: 600; margin: 14px 0 5px; }
        input[type=text], input[type=email], input[type=password], input[type=number], input[type=url], select {
            width: 100%; min-height: 44px; padding: 10px 12px; font-size: 14px;
            border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #0f172a;
        }
        input:focus, select:focus { outline: 2px solid #a5b4fc; border-color: #6366f1; }
        .check { display: flex; align-items: center; gap: 10px; font-size: 14px; margin-top: 16px; min-height: 44px; }
        .check input { width: 18px; height: 18px; accent-color: #4f46e5; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 44px; padding: 10px 20px; border-radius: 10px; border: 0;
            font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none;
        }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; }
        .btn-secondary { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .btn-secondary:hover { background: #f8fafc; }
        .btn[disabled], .btn.disabled { opacity: .5; pointer-events: none; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px; align-items: center; }
        .alert { border-radius: 10px; padding: 10px 14px; font-size: 14px; margin: 14px 0; }
        .alert-red { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-green { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
        @media (max-width: 540px) { .grid-2 { grid-template-columns: 1fr; } }
        pre { background: #0f172a; color: #e2e8f0; border-radius: 10px; padding: 14px; font-size: 12.5px; overflow-x: auto; margin: 10px 0; }
        .note { font-size: 12.5px; color: #94a3b8; margin-top: 6px; }
        .footer { margin-top: 22px; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="brand"><span class="brand-mark">&#128279;</span> {{ config('app.name', 'Shortl') }}</div>

    @php
        $installSteps = [
            1 => __('Requirements'),
            2 => __('License'),
            3 => __('Database'),
            4 => __('Admin'),
            5 => __('Finish'),
        ];
        $step = $step ?? 1;
    @endphp
    <div class="steps">
        @foreach($installSteps as $n => $label)
            <span class="step {{ $n === $step ? 'current' : ($n < $step ? 'done' : '') }}">
                <span class="dot">{{ $n < $step ? '✓' : $n }}</span> {{ $label }}
            </span>
        @endforeach
    </div>

    <div class="card">
        @yield('content')
    </div>

    <div class="footer">{{ config('app.name', 'Shortl') }} — {{ __('Web installer') }} v{{ config('install.version', '1.0.0') }}</div>
</body>
</html>
