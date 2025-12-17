@extends(app('auth.layout'))

@section('content')
<div class="w-100 row">
    <div class="col-12">
        <form method="POST" action="{{ route('register') }}" id="register-form" data-recaptcha-action="register">
            @csrf

            <!-- Name -->
            <div class="mb-3">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                @error('name')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
            </div>

            <!-- Email Address -->
            <div class="mb-3">
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                @error('email')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
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
                @error('password')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
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
                @error('password_confirmation')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
            </div>

            @include('usermanagement::components.recaptcha-field')

            <div class="d-flex justify-content-between align-items-center mb-3">
                <a class="text-decoration-none text-info" href="{{ route('login') }}">
                    {{ __('Already registered?') }}
                </a>

                <button type="submit" class="btn btn-sm btn-outline-info" id="register-button">
                    {{ __('Register') }}
                </button>
            </div>
        </form>

        @include('usermanagement::components.social-login-section')
    </div>
    {{-- <div class="col-6">
        @include('userinterface::components.form',
        [
            'id' => 'register-form'
        ])
    </div> --}}
</div>

@endsection