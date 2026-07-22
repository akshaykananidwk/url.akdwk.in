<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Payment issue') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background-color:#b45309;padding:20px 28px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">{{ site_name() }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 10px;font-size:20px;color:#0f172a;">{{ __('Action needed on your subscription') }}</h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
                                {{ __('Hi :name, the renewal payment for your :plan plan could not be completed or is overdue.', ['name' => $subscription->user->name, 'plan' => $subscription->plan?->name ?? __('current')]) }}
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #fde68a;background-color:#fffbeb;border-radius:10px;">
                                <tr>
                                    <td style="padding:14px 16px;font-size:13px;line-height:1.6;color:#92400e;">
                                        <strong>{{ __('Plan') }}:</strong> {{ $subscription->plan?->name ?? __('Subscription') }}<br>
                                        @if($subscription->ends_at)
                                            <strong>{{ __('Access ends') }}:</strong> {{ $subscription->ends_at->format('M j, Y') }}<br>
                                        @endif
                                        <strong>{{ __('Reminder') }}:</strong> {{ __('attempt :n of 3', ['n' => $attempt]) }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:16px 0 0;font-size:14px;line-height:1.6;color:#475569;">
                                {{ __('To keep your links, stats and custom domains running without interruption, please renew your plan before the end date. After the final reminder your account falls back to the free plan.') }}
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px auto 0;">
                                <tr>
                                    <td style="border-radius:10px;background-color:#4f46e5;">
                                        <a href="{{ route('billing.plans') }}" style="display:inline-block;padding:13px 26px;font-size:14px;font-weight:bold;color:#ffffff;text-decoration:none;">
                                            {{ __('Renew my plan') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px;background-color:#f8fafc;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;">
                                © {{ date('Y') }} {{ site_name() }}. {{ __('Already paid? You can ignore this email.') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
