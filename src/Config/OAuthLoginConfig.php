<?php
namespace Iquesters\UserManagement\Config;

class OAuthLoginConfig extends SocialLoginConfig
{
    public string $provider;
    public bool $enabled;
    public ?string $client_id;
    public ?string $client_secret;
    public ?string $redirect;
    public array $scopes;

    public function __construct(UserManagementConfig $config, array $data = [])
    {
        // This class is a **single provider**, so we don't use $providers from SocialLoginConfig
        $this->provider = $data['provider'] ?? 'oauth';
        $this->enabled = (bool) ($config->get(strtoupper($this->provider) . '_LOGIN') ?? $data['enabled'] ?? false);

        $conf = $data['config'] ?? [];
        $this->client_id = $config->get(strtoupper($this->provider) . '_CLIENT_ID') ?? $conf['client_id'] ?? null;
        $this->client_secret = $config->get(strtoupper($this->provider) . '_CLIENT_SECRET') ?? $conf['client_secret'] ?? null;
        $this->redirect = $config->get(strtoupper($this->provider) . '_REDIRECT') ?? $conf['redirect'] ?? null;
        $this->scopes = $conf['scopes'] ?? [];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'enabled'  => $this->enabled,
            'config'   => [
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect'      => $this->redirect,
                'scopes'        => $this->scopes,
            ],
        ];
    }
}