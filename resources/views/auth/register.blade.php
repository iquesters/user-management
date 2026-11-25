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
    })->map(function ($provider, $name) {
        return [
            'name' => is_string($name) ? $name : ($provider->identifier ?? null),
            'config' => $provider,
        ];
    })->filter(function ($providerData) {
        return !empty($providerData['name']);
    })->unique('name')->values();
    $hasSocialLogin = ($socialLoginConfig->enabled ?? false) && $socialProviders->isNotEmpty();
    // The schema form fetches a v3 token for 'register' (RecaptchaRule in RegisteredUserController).
    $recaptchaEnabled = $authConfig->recaptcha->enabled ?? false;
@endphp

<div class="w-100 row">
    @if ($showClassic)
    <div class="{{ $formCol }}">
        <div id="register-classic-section">
            <form method="POST" action="{{ route('register') }}" id="register-form" data-recaptcha-action="register">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                    @error('name')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                    @error('email')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>

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

                @if (\Iquesters\UserManagement\Support\Terms::required())
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="accept_terms" id="accept_terms" value="1" required @checked(old('accept_terms'))>
                        <label class="form-check-label small" for="accept_terms">
                            {{ __('I have read and agree to the') }}
                            @foreach (\Iquesters\UserManagement\Support\Terms::links() as $link)
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener">{{ $link['label'] }}</a>@if (! $loop->last) {{ __('and the') }} @endif
                            @endforeach
                            <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        @error('accept_terms')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

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
        </div>

        <div id="register-alternate-auth">
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
            'id' => 'register',
            'recaptchaAction' => $recaptchaEnabled ? 'register' : null,
        ])

        {{-- Classic column already shows social login when both are visible --}}
        @unless ($showClassic)
            @include('usermanagement::components.social-login-section')
        @endunless
    </div>
    @endif
</div>
@endsection
