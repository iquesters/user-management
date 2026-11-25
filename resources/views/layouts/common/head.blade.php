<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@if (class_exists(\Iquesters\UserInterface\Support\Assets::class))
    <title>{{ \Iquesters\UserInterface\Support\Assets::appName() }}</title>
    <link rel="icon" href="{{ \Iquesters\UserInterface\Support\Assets::faviconUrl() }}">
@else
    <title>{{ config('app.name') }}</title>
@endif

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