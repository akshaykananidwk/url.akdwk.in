<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Statistics report') }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 12px; color: #1e293b; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 24px 0 8px; }
        .muted { color: #64748b; font-size: 11px; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 8px; text-align: left; }
        th { background: #f1f5f9; font-size: 11px; text-transform: uppercase; letter-spacing: 0.03em; }
        td.num, th.num { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ __('Statistics report') }} — {{ $link->shortUrl() }}</h1>
    <p class="muted">
        {{ $link->destination }}<br>
        {{ __('Period') }}: {{ $from->format('M j, Y') }} – {{ $to->format('M j, Y') }} ·
        {{ __('Generated') }}: {{ now()->format('M j, Y H:i') }} · {{ site_name() }}
    </p>

    <h2>{{ __('Totals') }}</h2>
    <table>
        <thead>
            <tr>
                <th class="num">{{ __('Clicks') }}</th>
                <th class="num">{{ __('Unique visitors') }}</th>
                <th class="num">{{ __('QR scans') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="num">{{ number_format($totals['clicks']) }}</td>
                <td class="num">{{ number_format($totals['uniques']) }}</td>
                <td class="num">{{ number_format($totals['qr_scans']) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>{{ __('Daily clicks') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th class="num">{{ __('Clicks') }}</th>
                <th class="num">{{ __('Unique visitors') }}</th>
                <th class="num">{{ __('QR scans') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($series as $date => $row)
                <tr>
                    <td>{{ $date }}</td>
                    <td class="num">{{ number_format($row['clicks']) }}</td>
                    <td class="num">{{ number_format($row['uniques']) }}</td>
                    <td class="num">{{ number_format($row['qr_scans']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @foreach(['country' => __('Top countries'), 'referer' => __('Top referrers'), 'os' => __('Top platforms')] as $dimension => $heading)
        <h2>{{ $heading }}</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ $heading }}</th>
                    <th class="num">{{ __('Clicks') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($breakdowns[$dimension] as $row)
                    <tr>
                        <td>
                            @if($dimension === 'country' && strlen($row['key']) === 2 && class_exists(\Locale::class))
                                {{ \Locale::getDisplayRegion('-' . strtoupper($row['key']), app()->getLocale()) ?: $row['key'] }}
                            @else
                                {{ $row['key'] }}
                            @endif
                        </td>
                        <td class="num">{{ number_format($row['count']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">{{ __('No data for this period.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
