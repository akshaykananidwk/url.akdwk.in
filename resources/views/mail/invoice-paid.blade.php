<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Payment received') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background-color:#4f46e5;padding:20px 28px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">{{ site_name() }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 10px;font-size:20px;color:#0f172a;">{{ __('Thanks for your payment!') }}</h1>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#475569;">
                                {{ __('Hi :name, we have received your payment and your plan is now active. Here is a summary:', ['name' => $payment->user->name]) }}
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:10px;">
                                <tr>
                                    <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9;">{{ __('Invoice') }}</td>
                                    <td style="padding:10px 16px;font-size:13px;color:#0f172a;font-weight:bold;text-align:right;border-bottom:1px solid #f1f5f9;">{{ $payment->invoice_number ?: '#' . $payment->id }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9;">{{ __('Plan') }}</td>
                                    <td style="padding:10px 16px;font-size:13px;color:#0f172a;font-weight:bold;text-align:right;border-bottom:1px solid #f1f5f9;">{{ $payment->plan?->name ?? __('Plan') }} ({{ $payment->cycle }})</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9;">{{ __('Total') }}</td>
                                    <td style="padding:10px 16px;font-size:13px;color:#0f172a;font-weight:bold;text-align:right;border-bottom:1px solid #f1f5f9;">{{ format_money($payment->total, $payment->currency) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 16px;font-size:13px;color:#64748b;">{{ __('Date') }}</td>
                                    <td style="padding:10px 16px;font-size:13px;color:#0f172a;font-weight:bold;text-align:right;">{{ ($payment->paid_at ?? $payment->created_at)->format('M j, Y') }}</td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px auto 0;">
                                <tr>
                                    <td style="border-radius:10px;background-color:#4f46e5;">
                                        <a href="{{ route('billing.invoices') }}" style="display:inline-block;padding:13px 26px;font-size:14px;font-weight:bold;color:#ffffff;text-decoration:none;">
                                            {{ __('View invoice & download PDF') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px;background-color:#f8fafc;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;">© {{ date('Y') }} {{ site_name() }}. {{ __('All rights reserved.') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
