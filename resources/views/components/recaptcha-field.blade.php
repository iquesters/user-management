@php
    use Iquesters\Foundation\Support\ConfProvider;
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\UserManagement\Config\UserManagementKeys;
    use Iquesters\UserManagement\Config\RecaptchaConfig;

    /** @var \Iquesters\UserManagement\Config\UserManagementConfig $config */
    $config = ConfProvider::from(Module::USER_MGMT);

    $recaptchaEnabled = $config->recaptcha->enabled ?? false;
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