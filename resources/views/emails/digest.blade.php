@php
    $brand = brand_for($user);
    $accent = $brand['color'] ?? '#6366f1';
    $dashboardUrl = url('/dashboard');
    $top = $stats['topLink'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Your weekly :site recap', ['site' => site_name()]) }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e2e8f0;">
                    {{-- Header --}}
                    <tr>
                        <td style="padding:28px 32px 20px; border-bottom:1px solid #f1f5f9;">
                            <p style="margin:0; font-size:13px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:{{ $accent }};">{{ $brand['name'] ?? site_name() }}</p>
                            <h1 style="margin:6px 0 0; font-size:22px; line-height:1.3; color:#0f172a;">{{ __('Your week in review') }}</h1>
                            <p style="margin:6px 0 0; font-size:14px; color:#64748b;">
                                {{ __('Hi :name, here is how your links performed over the last 7 days.', ['name' => $user->name]) }}
                            </p>
                        </td>
                    </tr>

                    {{-- Stats --}}
                    <tr>
                        <td style="padding:20px 32px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="50%" style="padding:0 6px 12px 0;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border-radius:12px;">
                                            <tr><td style="padding:16px;">
                                                <p style="margin:0; font-size:26px; font-weight:700; color:#0f172a;">{{ format_number($stats['newClicks']) }}</p>
                                                <p style="margin:2px 0 0; font-size:13px; color:#64748b;">{{ __('New clicks') }}</p>
                                            </td></tr>
                                        </table>
                                    </td>
                                    <td width="50%" style="padding:0 0 12px 6px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border-radius:12px;">
                                            <tr><td style="padding:16px;">
                                                <p style="margin:0; font-size:26px; font-weight:700; color:#0f172a;">{{ format_number($stats['newLinks']) }}</p>
                                                <p style="margin:2px 0 0; font-size:13px; color:#64748b;">{{ __('New links') }}</p>
                                            </td></tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Top link --}}
                    @if($top)
                        <tr>
                            <td style="padding:8px 32px 4px;">
                                <p style="margin:0 0 8px; font-size:12px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:#94a3b8;">{{ __('Top performing link') }}</p>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0; border-radius:12px;">
                                    <tr><td style="padding:14px 16px;">
                                        <p style="margin:0; font-size:15px; font-weight:600; color:#0f172a;">{{ $top->title ?: $top->shortUrl() }}</p>
                                        <p style="margin:4px 0 0; font-size:13px; color:{{ $accent }}; word-break:break-all;">{{ $top->shortUrl() }}</p>
                                        <p style="margin:6px 0 0; font-size:13px; color:#64748b;">{{ __(':n total clicks', ['n' => format_number($top->clicks_count)]) }}</p>
                                    </td></tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- CTA --}}
                    <tr>
                        <td align="center" style="padding:24px 32px 8px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr><td style="border-radius:10px; background-color:{{ $accent }};">
                                    <a href="{{ $dashboardUrl }}" style="display:inline-block; padding:12px 28px; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none;">{{ __('Open your dashboard') }}</a>
                                </td></tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 32px 28px;">
                            <p style="margin:0; font-size:12px; line-height:1.6; color:#94a3b8; text-align:center;">
                                {{ __('You are receiving this because you have an account on :site.', ['site' => site_name()]) }}<br>
                                <a href="{{ url('/account') }}" style="color:#94a3b8;">{{ __('Manage email preferences') }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
