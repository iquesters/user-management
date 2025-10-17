@php
    use Iquesters\Foundation\Support\ConfigProvider;
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\UserManagement\Config\UserManagementKeys;

    $layout = ConfigProvider::from(Module::USER_MGMT)
        ->get(UserManagementKeys::AUTH_LAYOUT);
@endphp

@extends($layout)

@section('content')
<div class="w-100">
    <form method="POST" action="{{ route('password.store') }}" id="passwordResetForm" data-recaptcha-action="password_reset">
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

        @include('usermanagement::components.recaptcha-field')

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-sm btn-outline-info" id="submitButton">
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
        passwordHelp.style.display = password.length > 0 && !isValid ? 'block' : 'none';
        return isValid;
    }

    function checkPasswordMatch() {
        const match = passwordInput.value === confirmPasswordInput.value;
        passwordMatch.textContent = match ? '' : 'Passwords do not match';
        return match;
    }

    function validateForm() {
        submitButton.disabled = !(validatePassword() && checkPasswordMatch());
    }

    passwordInput.addEventListener('input', validateForm);
    confirmPasswordInput.addEventListener('input', validateForm);
    validateForm();
});
</script>

@endsection