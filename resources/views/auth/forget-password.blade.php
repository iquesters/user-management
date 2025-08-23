@extends(config('usermanagement.layout_auth'))

@section('content')
<div class="w-100">
    <div class="mb-4 text-muted">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <form method="POST" action="{{ route('password.email') }}" id="passwordEmailForm">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @if ($errors->has('email'))
            <div class="text-danger mt-2">
                {{ $errors->first('email') }}
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
                {{ __('Email Password Reset Link') }}
            </button>
        </div>
    </form>
</div>

<script>
    grecaptcha.ready(function() {
        document.getElementById('passwordEmailForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = document.getElementById('submitButton');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'password_reset_link'})
            .then(function(token) {
                document.getElementById('recaptcha_token').value = token;
                document.getElementById('passwordEmailForm').submit();
            })
            .catch(function(error) {
                submitButton.disabled = false;
                submitButton.innerHTML = '{{ __('Email Password Reset Link') }}';

                const errorDiv = document.getElementById('recaptcha-client-error');
                errorDiv.textContent = 'Security verification failed. Please try again.';
                errorDiv.classList.remove('d-none');
            });
        });
    });
</script>
@endsection