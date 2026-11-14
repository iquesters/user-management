@extends(app('auth.layout'))

@php
    // auth_form_type conf: classic (default) | schema | both
    $showClassic = \Iquesters\UserManagement\Support\AuthFormSchema::showsClassic();
    $showSchema = \Iquesters\UserManagement\Support\AuthFormSchema::showsSchema();
    $formCol = $showClassic && $showSchema ? 'col-6' : 'col-12';
@endphp
@unless ($showClassic && $showSchema)
    @section('auth-card-class', 'col-md-7 col-lg-4')
@endunless

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
    // The schema form fetches a v3 token for 'login' (RecaptchaRule in LoginRequest).
    $recaptchaEnabled = $authConfig->recaptcha->enabled ?? false;
@endphp

<div class="w-100 row">
    @if ($showClassic)
    <div class="{{ $formCol }}">
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

        <div id="alternate-auth-options">
            @if ($hasSocialLogin)
                <div class="d-flex align-items-center my-3">
                    <hr class="flex-grow-1">
                    <span class="mx-2 text-muted">or</span>
                    <hr class="flex-grow-1">
                </div>
            @endif

            @include('usermanagement::components.social-login-section', ['showDivider' => false])
        </div>
    </div>
    @endif
    @if ($showSchema)
    <div class="{{ $formCol }}">
        @include('userinterface::components.form',
        [
            'id' => 'login-with-password',
            'recaptchaAction' => $recaptchaEnabled ? 'login' : null,
        ])

        {{-- Classic column already shows social login when both are visible --}}
        @unless ($showClassic)
            @include('usermanagement::components.social-login-section')
        @endunless
    </div>
    @endif

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
