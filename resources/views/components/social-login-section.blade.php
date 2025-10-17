@php
    use Iquesters\Foundation\Support\ConfigProvider;
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\UserManagement\Config\UserManagementKeys;

    $config = ConfigProvider::from(Module::USER_MGMT);

    // Get the entire social_login config
    $socialLoginRaw = $config->get('social_login');
    
    // Check if social login is enabled - use the specific key
    $socialEnabled = $config->get(UserManagementKeys::SOCIAL_LOGIN_ENABLED) ?? $socialLoginRaw['enabled'] ?? false;
    
    // Get providers
    $providers = $socialLoginRaw['providers'] ?? [];
    
    // Apply environment overrides to providers
    foreach ($providers as &$provider) {
        $providerName = strtoupper($provider['provider']);
        
        // Check if provider is enabled via environment
        $providerEnabled = $config->get("{$providerName}_LOGIN");
        if (!is_null($providerEnabled)) {
            $provider['enabled'] = filter_var($providerEnabled, FILTER_VALIDATE_BOOLEAN);
        }
    }
    
    // Filter active ones
    $activeProviders = collect($providers)->filter(fn($provider) => $provider['enabled']);

@endphp

@if ($socialEnabled && $activeProviders->isNotEmpty())
    <!-- 🔹 Divider -->
    <div class="d-flex align-items-center my-3">
        <hr class="flex-grow-1">
        <span class="mx-2 text-muted">or</span>
        <hr class="flex-grow-1">
    </div>

    <!-- 🔹 Social Login Buttons -->
    @foreach ($activeProviders as $provider)
        @includeIf("usermanagement::components.signin-with-{$provider['provider']}-button", [
            'provider' => $provider['provider'],
            'config' => $provider['config'] ?? [],
        ])
    @endforeach
@endif