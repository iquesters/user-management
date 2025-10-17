<?php
namespace Iquesters\UserManagement\Config;

class SocialLoginConfig
{
    public bool $enabled;
    /** @var array<string, OAuthLoginConfig> */
    public array $providers = [];

    public function __construct(UserManagementConfig $config, array $data = [])
    {
        // Global social login enabled
        $this->enabled = (bool) ($config->get(UserManagementKeys::SOCIAL_LOGIN_ENABLED) ?? $data['enabled'] ?? false);

        $providers = $data['providers'] ?? [];
        foreach ($providers as $name => $providerData) {
            $this->providers[$name] = $this->makeProvider($name, $providerData, $config);
        }
    }

    protected function makeProvider(string $name, array $data, UserManagementConfig $config): OAuthLoginConfig
    {
        return match (strtolower($name)) {
            'google' => new GoogleLoginConfig($config, $data),
            default  => new OAuthLoginConfig($config, array_merge(['provider' => $name], $data)),
        };
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}