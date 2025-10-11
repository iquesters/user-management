<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Auth</title>

@if (config('usermanagement.recaptcha.enabled'))
    <script>
        window.recaptchaSiteKey = '{{ config('usermanagement.recaptcha.site_key') }}';
    </script>
    
    <!-- reCAPTCHA v3 -->
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('usermanagement.recaptcha.enabled') ? config('usermanagement.recaptcha.site_key') : 'dummy' }}"></script>
@endif

@include('usermanagement::layouts.common.css')