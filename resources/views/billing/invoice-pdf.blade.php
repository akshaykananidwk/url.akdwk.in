<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $payment->invoice_number ?: 'Invoice #' . $payment->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 12px; color: #1e293b; margin: 36px; }
        .header { width: 100%; margin-bottom: 28px; }
        .header td { vertical-align: top; }
        .site { font-size: 20px; font-weight: bold; color: #4f46e5; }
        .company { white-space: pre-wrap; color: #64748b; font-size: 11px; margin-top: 6px; }
        .invoice-meta { text-align: right; }
        .invoice-title { font-size: 22px; font-weight: bold; letter-spacing: 1px; color: #0f172a; }
        .paid-stamp {
            display: inline-block; margin-top: 8px; padding: 5px 16px;
            border: 2px solid #16a34a; color: #16a34a; font-weight: bold; font-size: 13px;
            border-radius: 5px; letter-spacing: 2px;
        }
        .meta-row { margin-top: 8px; color: #475569; }
        .billto { margin: 20px 0 26px; }
        .billto .label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 4px; }
        .billto .name { font-weight: bold; font-size: 13px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th {
            text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
            color: #64748b; border-bottom: 2px solid #e2e8f0; padding: 8px 6px;
        }
        table.items th.amount, table.items td.amount { text-align: right; }
        table.items td { padding: 9px 6px; border-bottom: 1px solid #f1f5f9; }
        table.items tr.total td { border-top: 2px solid #e2e8f0; border-bottom: 0; font-weight: bold; font-size: 14px; }
        .footer { margin-top: 40px; text-align: center; color: #94a3b8; font-size: 11px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="site">{{ site_name() }}</div>
                @if(setting('invoice_company_details'))
                    <div class="company">{{ setting('invoice_company_details') }}</div>
                @endif
            </td>
            <td class="invoice-meta">
                <div class="invoice-title">{{ __('INVOICE') }}</div>
                <div class="paid-stamp">{{ __('PAID') }}</div>
                <div class="meta-row">
                    <strong>{{ $payment->invoice_number ?: '#' . $payment->id }}</strong><br>
                    {{ __('Date') }}: {{ ($payment->paid_at ?? $payment->created_at)->format('M j, Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="billto">
        <div class="label">{{ __('Billed to') }}</div>
        <div class="name">{{ $payment->user->name }}</div>
        <div>{{ $payment->user->email }}</div>
        @if(!empty($payment->tax_details['tax_id']))
            <div>{{ __('Tax ID') }}: {{ $payment->tax_details['tax_id'] }}</div>
        @endif
    </div>

    <table class="items">
        <tr>
            <th>{{ __('Description') }}</th>
            <th class="amount">{{ __('Amount') }}</th>
        </tr>
        <tr>
            <td>
                {{ $payment->plan?->name ?? __('Plan') }} —
                @switch($payment->cycle)
                    @case('yearly') {{ __('yearly subscription') }} @break
                    @case('lifetime') {{ __('lifetime access') }} @break
                    @default {{ __('monthly subscription') }}
                @endswitch
            </td>
            <td class="amount">{{ format_money($payment->amount, $payment->currency) }}</td>
        </tr>
        @if((float) $payment->discount_amount > 0)
            <tr>
                <td>{{ __('Discount') }}</td>
                <td class="amount">-{{ format_money($payment->discount_amount, $payment->currency) }}</td>
            </tr>
        @endif
        @if((float) $payment->tax_amount > 0)
            <tr>
                <td>
                    {{ $payment->tax_details['name'] ?? __('Tax') }}
                    @if(isset($payment->tax_details['rate']))
                        ({{ rtrim(rtrim(number_format((float) $payment->tax_details['rate'], 2), '0'), '.') }}%)
                    @endif
                </td>
                <td class="amount">{{ format_money($payment->tax_amount, $payment->currency) }}</td>
            </tr>
        @endif
        <tr class="total">
            <td>{{ __('Total') }}</td>
            <td class="amount">{{ format_money($payment->total, $payment->currency) }}</td>
        </tr>
    </table>

    <div class="footer">
        {{ __('Thank you for your business!') }}<br>
        {{ site_name() }} — {{ setting('site_url', config('app.url')) }}
    </div>
</body>
</html>
