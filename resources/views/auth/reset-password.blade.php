@extends(config('usermanagement.layout_auth'))

@section('content')
<div class="w-100">
    <form method="POST" action="{{ route('password.store') }}" id="passwordResetForm">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email', $request->email) }}" required readonly autofocus autocomplete="username">
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
                <input id="password" class="form-control" type="password" name="password" required autocomplete="new-password">
                <button class="btn btn-outline-secondary toggle-password" type="button">
                    <i class="fas fa-eye-slash"></i>
                </button>
            </div>
            <small id="passwordHelp" class="form-text text-danger">
                Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character (@ $ ! % * ? &).
            </small>
            @if ($errors->has('password'))
            <div class="text-danger mt-2">
                {{ $errors->first('password') }}
            </div>
            @endif
        </div>

        <!-- Confirm Password -->
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
            <div class="input-group">
                <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password">
                <button class="btn btn-outline-secondary toggle-password" type="button">
                    <i class="fas fa-eye-slash"></i>
                </button>
            </div>
            <div id="passwordMatch" class="text-danger small"></div>
            @if ($errors->has('password_confirmation'))
            <div class="text-danger mt-2">
                {{ $errors->first('password_confirmation') }}
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

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-sm btn-outline-dark" id="submitButton">
                {{ __('Reset Password') }}
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('password_confirmation');
    const passwordHelp = document.getElementById('passwordHelp');
    const passwordMatch = document.getElementById('passwordMatch');
    const submitButton = document.getElementById('submitButton');

    function validatePassword() {
        const password = passwordInput.value;
        const isValid = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(password);
        
        if (password.length > 0) {
            passwordHelp.style.display = isValid ? 'none' : 'block';
        } else {
            passwordHelp.style.display = 'none';
        }
        
        return isValid;
    }

    function checkPasswordMatch() {
        if (passwordInput.value !== confirmPasswordInput.value) {
            passwordMatch.textContent = 'Passwords do not match';
            return false;
        } else {
            passwordMatch.textContent = '';
            return true;
        }
    }

    function validateForm() {
        const isPasswordValid = validatePassword();
        const isMatchValid = checkPasswordMatch();
        submitButton.disabled = !(isPasswordValid && isMatchValid);
    }

    passwordInput.addEventListener('input', validateForm);
    confirmPasswordInput.addEventListener('input', validateForm);

    // Initial validation
    validateForm();

    // reCAPTCHA handling
    grecaptcha.ready(function() {
        document.getElementById('passwordResetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
            
            grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'password_reset'})
            .then(function(token) {
                document.getElementById('recaptcha_token').value = token;
                document.getElementById('passwordResetForm').submit();
            })
            .catch(function(error) {
                submitButton.disabled = false;
                submitButton.innerHTML = '{{ __('Reset Password') }}';

                const errorDiv = document.getElementById('recaptcha-client-error');
                errorDiv.textContent = 'Security verification failed. Please try again.';
                errorDiv.classList.remove('d-none');
            });
        });
    });
});
</script>
@endsection