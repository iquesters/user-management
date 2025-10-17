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
    <div class="mb-4 text-muted">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <form method="POST" action="{{ route('password.email') }}" id="passwordEmailForm" data-recaptcha-action="password_reset_link">
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

        @include('usermanagement::components.recaptcha-field')

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-sm btn-outline-info" id="submitButton">
                {{ __('Email Password Reset Link') }}
            </button>
        </div>
    </form>
</div>

@endsection