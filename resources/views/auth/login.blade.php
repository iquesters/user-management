@php
    use Iquesters\Foundation\Support\ConfigProvider;
    use Iquesters\Foundation\Support\ConfProvider;
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\UserManagement\Config\UserManagementKeys;
    use Iquesters\UserManagement\Config\GoogleConf;

    // $layout = ConfigProvider::from(Module::USER_MGMT)
    //     ->get(UserManagementKeys::AUTH_LAYOUT);
    $umConf = ConfProvider::from(Module::NEW_USER_MGMT);
    $flatten = $umConf->getFlattenConfig(null, true);
    dd($flatten);
    // dd($umConf);
    // $umConf->social_login->o_auth_providers = [new GoogleConf()];
    // $keys = $umConf->generateKeys();
    // dd($keys);

    // $test = [['key' => 'AUTH_LAYOUT', 'value' => 'hola']];
    // $test = [
    //     ['key' => '$RECAPTCHA~ENABLED', 'value' => true],
    //     ['key' => '$RECAPTCHA~SITE_KEY', 'value' => 'new_site_key'],
    //     ['key' => '$SOCIAL_LOGIN~@O_AUTH_PROVIDERS~$GOOGLE~CLIENT_ID', 'value' => 'new_client_id'],
    //     ['key' => 'AUTH_LAYOUT', 'value' => 'hola']
    // ];
    // $umConf = $umConf->decipherConf($test);

    // dd($umConf);
@endphp

@extends($layout)

@section('content')
<div class="w-100">
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

    @include('usermanagement::components.social-login-section')

</div>
@endsection