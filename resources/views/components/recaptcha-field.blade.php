@php
    use Iquesters\Foundation\Support\ConfigProvider;
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\UserManagement\Config\UserManagementKeys;

    $config = ConfigProvider::from(Module::USER_MGMT);
    
    // Try multiple approaches to get reCAPTCHA enabled status
    $recaptchaEnabled = $config->get(UserManagementKeys::RECAPTCHA_ENABLED);
    
    // If direct key doesn't work, try getting from the recaptcha array
    if (is_null($recaptchaEnabled)) {
        $recaptchaConfig = $config->get('recaptcha');
        $recaptchaEnabled = is_array($recaptchaConfig) ? ($recaptchaConfig['enabled'] ?? false) : false;
    }
    
@endphp

@if ($recaptchaEnabled)
    <!-- Hidden reCAPTCHA Token -->
    <input type="hidden" name="recaptcha_token" id="recaptcha_token">
    
    @if ($errors->has('recaptcha_token'))
        <div class="text-danger mt-2" id="recaptcha-server-error">
            {{ $errors->first('recaptcha_token') }}
        </div>
    @else
        <div class="text-danger mt-2 d-none" id="recaptcha-client-error"></div>
    @endif
@endif