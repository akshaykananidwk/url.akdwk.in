@php($captchaProvider = setting('captcha_provider'))
@php($captchaSiteKey = setting('captcha_site_key'))
@if($captchaProvider && $captchaSiteKey)
    @if($captchaProvider === 'recaptcha2')
        <div class="g-recaptcha" data-sitekey="{{ $captchaSiteKey }}"></div>
        @error('captcha')<p class="error">{{ $message }}</p>@enderror
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @elseif($captchaProvider === 'recaptcha3')
        <input type="hidden" name="g-recaptcha-response" class="js-recaptcha3-token">
        @error('captcha')<p class="error">{{ $message }}</p>@enderror
        <script src="https://www.google.com/recaptcha/api.js?render={{ $captchaSiteKey }}"></script>
        <script>
            (function () {
                var inputs = document.querySelectorAll('.js-recaptcha3-token');
                var input = inputs[inputs.length - 1];
                if (!input) return;
                var form = input.closest('form');
                if (!form || form.dataset.recaptcha3Bound) return;
                form.dataset.recaptcha3Bound = '1';
                form.addEventListener('submit', function (e) {
                    if (form.dataset.recaptcha3Done) return;
                    e.preventDefault();
                    grecaptcha.ready(function () {
                        grecaptcha.execute(@json($captchaSiteKey), { action: 'submit' }).then(function (token) {
                            input.value = token;
                            form.dataset.recaptcha3Done = '1';
                            form.submit();
                        });
                    });
                });
            })();
        </script>
    @elseif($captchaProvider === 'hcaptcha')
        <div class="h-captcha" data-sitekey="{{ $captchaSiteKey }}"></div>
        @error('captcha')<p class="error">{{ $message }}</p>@enderror
        <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
    @endif
@endif
