@if (config('usermanagement.social_login.enabled'))
    @php
        $providers = config('usermanagement.social_login.providers', []);
        $activeProviders = collect($providers)->filter(fn($provider) => $provider['enabled'] ?? false);
    @endphp

    @if ($activeProviders->isNotEmpty())
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
@endif