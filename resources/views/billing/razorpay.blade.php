<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('Secure payment') }} — {{ site_name() }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f8fafc; color: #0f172a; margin: 0;
            min-height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center;
        }
        .box { max-width: 380px; padding: 32px 24px; }
        .spinner {
            width: 42px; height: 42px; margin: 0 auto 16px;
            border: 3px solid rgba(99, 102, 241, .25); border-top-color: #6366f1;
            border-radius: 50%; animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        h1 { font-size: 18px; margin: 0 0 6px; }
        p { margin: 0 0 20px; font-size: 14px; color: #64748b; }
        button {
            min-height: 46px; padding: 0 24px; border: 0; border-radius: 10px;
            background: #4f46e5; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer;
        }
        a { color: #6366f1; font-size: 13px; display: inline-block; margin-top: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner" aria-hidden="true"></div>
        <h1>{{ __('Opening secure checkout…') }}</h1>
        <p>{{ __('Amount') }}: <strong>{{ format_money($payment->total, $payment->currency) }}</strong> · {{ __('Order #:id', ['id' => $payment->id]) }}</p>
        <button type="button" id="pay-btn">{{ __('Pay now') }}</button>
        <br>
        <a href="{{ route('billing.plans') }}">{{ __('Cancel and go back') }}</a>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        (function () {
            var callbackUrl = @json($callbackUrl);
            var options = {
                key: @json($keyId),
                amount: {{ (int) round($payment->total * 100) }},
                currency: @json($payment->currency),
                name: @json(site_name()),
                description: @json(__('Order #:id', ['id' => $payment->id])),
                order_id: @json($orderId),
                prefill: {
                    name: @json($payment->user->name ?? ''),
                    email: @json($payment->user->email ?? ''),
                },
                handler: function (response) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = callbackUrl;
                    var fields = {
                        _token: @json(csrf_token()),
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature,
                    };
                    Object.keys(fields).forEach(function (name) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = fields[name] || '';
                        form.appendChild(input);
                    });
                    document.body.appendChild(form);
                    form.submit();
                },
                modal: {
                    ondismiss: function () {
                        window.location = @json(route('billing.plans'));
                    },
                },
                theme: { color: '#4f46e5' },
            };

            var rzp = new Razorpay(options);
            document.getElementById('pay-btn').addEventListener('click', function () { rzp.open(); });
            rzp.open();
        })();
    </script>
</body>
</html>
