<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Auth</title>

@php
    use Iquesters\Foundation\Support\ConfigProvider;
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\UserManagement\Config\UserManagementKeys;

    $config = ConfigProvider::from(Module::USER_MGMT);
    
    // Get the recaptcha config array
    $recaptchaConfig = $config->get('recaptcha');
    
    // Apply environment variable overrides to the nested array
    if (is_array($recaptchaConfig)) {
        $recaptchaConfig['site_key'] = $config->get(UserManagementKeys::RECAPTCHA_SITE_KEY) ?? $recaptchaConfig['site_key'];
        $recaptchaConfig['enabled'] = $config->get(UserManagementKeys::RECAPTCHA_ENABLED) ?? $recaptchaConfig['enabled'];
    }
    $recaptchaEnabled = is_array($recaptchaConfig) ? ($recaptchaConfig['enabled'] ?? false) : false;
    $recaptchaSiteKey = is_array($recaptchaConfig) ? ($recaptchaConfig['site_key'] ?? null) : null;
@endphp

@if ($recaptchaEnabled && $recaptchaSiteKey)
    <script>
        window.recaptchaSiteKey = '{{ $recaptchaSiteKey }}';
    </script>
    
    <!-- reCAPTCHA v3 -->
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}"></script>
@endif

@include('usermanagement::layouts.common.css')