@php
    use Iquesters\Foundation\Support\ConfigProvider; 
    use Iquesters\Foundation\Enums\Module;
    
    $config = ConfigProvider::from(Module::USER_MGMT);
    
    // Use the get() method to get the transformed SocialLoginConfig object
    $socialLogin = $config->get('social_logins');
    
    // Check if global social login is enabled
    $socialEnabled = $socialLogin->isEnabled();

    // Get only enabled providers
    $activeProviders = collect($socialLogin->providers)
        ->filter(fn($provider) => $provider->isEnabled());
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
            'config'   => $provider->toArray()['config'] ?? [],
        ])
    @endforeach
@endif