@php
    use Iquesters\Foundation\Support\ConfProvider; 
    use Iquesters\Foundation\Enums\Module;
    
    $config = ConfProvider::from(Module::USER_MGMT);
    $socialLogin = $config->social_login;
    
    // Check if global social login is enabled
    $socialEnabled = $socialLogin->enabled;

    $providers = $socialLogin->o_auth_providers ?? [];

    // Get only enabled providers
    $activeProviders = collect($providers)->filter(function ($provider) {
        // Some of your config objects use ->enabled instead of ->isEnabled()
        return $provider->enabled ?? false;
    });
@endphp

@if ($socialEnabled && $activeProviders->isNotEmpty())
    <div class="d-flex align-items-center my-3">
        <hr class="flex-grow-1">
        <span class="mx-2 text-muted">or</span>
        <hr class="flex-grow-1">
    </div>

    @foreach ($activeProviders as $name => $provider)
        @includeIf("usermanagement::components.signin-with-{$name}-button", [
            'provider' => $name,
            'config'   => $provider,
        ])
    @endforeach
@endif