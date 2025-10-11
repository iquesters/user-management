@if (config('usermanagement.recaptcha.enabled'))
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