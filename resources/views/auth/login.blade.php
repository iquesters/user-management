@extends(config('usermanagement.layout_auth'))

@section('content')
<div class="w-100">
    <form method="POST" action="{{ route('login') }}" id="login-form">
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
        <div class="mb-2">
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

        @if (config('usermanagement.recaptcha.enabled'))
            <!-- Hidden reCAPTCHA Token -->
            <input type="hidden" name="recaptcha_token" id="recaptcha_token">
            @error('recaptcha_token')
                <div class="text-danger mt-2" id="recaptcha-server-error">{{ $message }}</div>
            @else
                <div class="text-danger mt-2 d-none" id="recaptcha-client-error"></div>
            @enderror
        @endif
    
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

    <!-- 🔹 Divider -->
    <div class="d-flex align-items-center my-3">
        <hr class="flex-grow-1">
        <span class="mx-2 text-muted">or</span>
        <hr class="flex-grow-1">
    </div>

    <!-- 🔹 Google Login Button -->
    @include('usermanagement::components.signin-with-google-button')


</div>

@if (config('usermanagement.recaptcha.enabled'))
    <script>
        grecaptcha.ready(function() {
            document.getElementById('login-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitButton = document.getElementById('login-button');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
                
                grecaptcha.execute('{{ config('usermanagement.recaptcha.site_key') }}', {action: 'login'})
                .then(function(token) {
                    document.getElementById('recaptcha_token').value = token;
                    document.getElementById('login-form').submit();
                })
                .catch(function() {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '{{ __('Log in') }}';

                    const errorDiv = document.getElementById('recaptcha-client-error');
                    errorDiv.textContent = 'Security verification failed. Please try again.';
                    errorDiv.classList.remove('d-none');
                });
            });
        });
    </script>
@endif
@endsection