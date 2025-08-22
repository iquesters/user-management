@extends('layouts.auth')

@section('content')
<div class="w-100">
    <form method="POST" action="{{ route('login') }}" id="login-form">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @if ($errors->has('email'))
            <div class="text-danger mt-2">
                {{ $errors->first('email') }}
            </div>
            @endif
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <div class="input-group">
                <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">
                <button class="btn btn-outline-secondary toggle-password" type="button">
                    <i class="fas fa-eye-slash"></i>
                </button>
            </div>
            @if ($errors->has('password'))
            <div class="text-danger mt-2">
                {{ $errors->first('password') }}
            </div>
            @endif
        </div>

        <!-- Hidden reCAPTCHA Token -->
        <input type="hidden" name="recaptcha_token" id="recaptcha_token">
        @if ($errors->has('recaptcha_token'))
            <div class="text-danger mt-2" id="recaptcha-server-error">
                {{ $errors->first('recaptcha_token') }}
            </div>
        @else
            <div class="text-danger mt-2 d-none" id="recaptcha-client-error">
                <!-- This will be filled by JS if needed -->
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center">
            @if (Route::has('password.request'))
            <a class="text-decoration-none text-muted" href="{{ route('password.request') }}">
                {{ __('Forgot password?') }}
            </a>
            @endif

            <button type="submit" class="btn btn-sm btn-outline-dark" id="login-button">
                {{ __('Log in') }}
            </button>
        </div>
    </form>
</div>

<script>
    grecaptcha.ready(function() {
        // Execute reCAPTCHA when form is submitted
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Disable submit button to prevent double submission
            const submitButton = document.getElementById('login-button');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            
            grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'login'})
            .then(function(token) {
                document.getElementById('recaptcha_token').value = token;
                document.getElementById('login-form').submit();
            })
            .catch(function(error) {
                // Re-enable button if there's an error
                submitButton.disabled = false;
                submitButton.innerHTML = '{{ __('Log in') }}';

                const errorDiv = document.getElementById('recaptcha-client-error');
                errorDiv.textContent = 'Security verification failed. Please try again.';
                errorDiv.classList.remove('d-none');
            });
        });
    });
</script>
@endsection