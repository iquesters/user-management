<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Auth</title>

@php
    use Iquesters\Foundation\Support\ConfProvider;
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\UserManagement\Config\RecaptchaConfig;

    $recaptcha = ConfProvider::from(Module::USER_MGMT)->recaptcha;
@endphp

@if ($recaptcha->enabled)
    <script>
        window.recaptchaSiteKey = '{{ $recaptcha->site_key }}';
    </script>

    <!-- reCAPTCHA v3 -->
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha->site_key }}"></script>
@endif

@include('usermanagement::layouts.common.css')