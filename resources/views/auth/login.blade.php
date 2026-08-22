@extends(app('auth.layout'))

@section('content')
@php
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\Foundation\Support\ConfProvider;

    $authConfig = ConfProvider::from(Module::USER_MGMT);
    $socialLoginConfig = $authConfig->social_login;
    $socialProviders = collect($socialLoginConfig->o_auth_providers ?? [])->filter(function ($provider) {
        return $provider->enabled ?? false;
    });
    $hasSocialLogin = ($socialLoginConfig->enabled ?? false) && $socialProviders->isNotEmpty();
    $hasWhatsAppLogin = ($socialLoginConfig->enabled ?? false) && (bool) (($authConfig->whatsapp_login->enabled ?? false));
    // The schema-driven form column has no recaptcha field yet, so submitting
    // it would always fail validation once recaptcha is turned on. Fall back
    // to the classic form alone until that's built.
    $recaptchaEnabled = $authConfig->recaptcha->enabled ?? false;
@endphp

<div class="w-100 row">
    <div class="col-6">
        <div id="login-password-section">
            <form method="POST" action="{{ route('login') }}" id="login-form" data-recaptcha-action="login">
                @csrf

                <!-- Email Address -->
                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                    @error('email')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="password" class="form-label">{{ __('Password') }}</label>
                        @if (Route::has('password.request'))
                            <a class="text-decoration-none text-info" href="{{ route('password.request') }}">
                                {{ __('Forgot password?') }}
                            </a>
                        @endif
                    </div>
                    <div class="input-group">
                        <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>

                @include('usermanagement::components.recaptcha-field')
            
                <div class="d-flex justify-content-between align-items-center mb-3">
                    @if (Route::has('register'))
                    <a class="text-decoration-none text-info" href="{{ route('register') }}">
                        {{ __('Create a new account') }}
                    </a>
                    @endif

                    <button type="submit" class="btn btn-sm btn-outline-info" id="login-button">
                        {{ __('Log in') }}
                    </button>
                </div>
            </form>
        </div>

        <div id="whatsapp-login-panel" class="card border-0 bg-light-subtle d-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                    <div>
                        <h6 class="mb-1">Log in with WhatsApp OTP</h6>
                        <p class="text-muted small mb-0">
                            Enter your WhatsApp number with country code. If it matches an existing account, we will send a verification code.
                        </p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="whatsapp-login-back">
                        Or use classic method
                    </button>
                </div>

                {{-- Intentional inline auth-state structure for the login screen; @todo move the WhatsApp OTP UI behavior into dedicated auth assets later. --}}
                <form id="whatsapp-otp-form" class="d-grid gap-3">
                    <div>
                        <label for="whatsapp-phone" class="form-label">WhatsApp number</label>
                        <input
                            id="whatsapp-phone"
                            name="phone"
                            type="tel"
                            class="form-control"
                            placeholder="+919876543210"
                            inputmode="tel"
                            autocomplete="tel"
                        >
                    </div>

                    <div id="whatsapp-otp-entry" class="d-none">
                        <label for="whatsapp-otp" class="form-label">Verification code</label>
                        <input
                            id="whatsapp-otp"
                            name="otp"
                            type="text"
                            class="form-control"
                            placeholder="Enter OTP"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                        >
                    </div>

                    <div id="whatsapp-otp-feedback" class="small text-muted" aria-live="polite"></div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="whatsapp-send-otp">
                            Send OTP on WhatsApp
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success d-none" id="whatsapp-verify-otp">
                            Verify and log in
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="whatsapp-resend-otp">
                            Resend OTP
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="alternate-auth-options">
            @if ($hasSocialLogin || $hasWhatsAppLogin)
                {{-- Shared auth-option divider keeps Google visible first and prevents WhatsApp from visually replacing existing social login. --}}
                <div class="d-flex align-items-center my-3">
                    <hr class="flex-grow-1">
                    <span class="mx-2 text-muted">or</span>
                    <hr class="flex-grow-1">
                </div>
            @endif

            @include('usermanagement::components.social-login-section', ['showDivider' => false])
            @include('usermanagement::components.whatsapp-login-section', ['showDivider' => false])
        </div>
    </div>
    @unless ($recaptchaEnabled)
    <div class="col-6">
        @include('userinterface::components.form',
        [
            'id' => 'login-with-password'
        ])
    </div>
    @endunless

</div>

<script>
    // The generic form engine submits via fetch() and expects JSON back, so a
    // successful login (which returns JSON here, see AuthenticatedSessionController)
    // needs an explicit navigation — nothing else on the page will redirect the browser.
    window.addEventListener('lab-form:submitted', function (event) {
        if (event.detail?.formId !== 'login-with-password') {
            return;
        }

        const redirectUrl = event.detail?.response?.data?.redirect_url;
        window.location.href = redirectUrl || '{{ route('dashboard') }}';
    });
</script>
@endsection
