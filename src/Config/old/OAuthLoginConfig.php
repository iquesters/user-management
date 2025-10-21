<?php
namespace Iquesters\UserManagement\Config;

class OAuthLoginConfig
{
    public string $provider;
    public bool $enabled;
    public ?string $client_id;
    public ?string $client_secret;
    public ?string $redirect;
    public array $scopes;

    public function __construct(UserManagementConfig $config, array $data = [])
    {
        $this->provider = $data['provider'] ?? 'oauth';
        
        // Build the nested key path dynamically
        // e.g., SOCIAL_LOGINS~PROVIDERS~GOOGLE~ENABLED
        $providerUpper = strtoupper($this->provider);
        $baseKey = "SOCIAL_LOGINS~PROVIDERS~{$providerUpper}";
        
        $this->enabled = (bool) ($config->get("{$baseKey}~ENABLED") ?? $data['enabled'] ?? false);

        $conf = $data['config'] ?? [];
        $this->client_id = $config->get("{$baseKey}~CONFIG~CLIENT_ID") ?? $conf['client_id'] ?? null;
        $this->client_secret = $config->get("{$baseKey}~CONFIG~CLIENT_SECRET") ?? $conf['client_secret'] ?? null;
        $this->redirect = $config->get("{$baseKey}~CONFIG~REDIRECT") ?? $conf['redirect'] ?? null;
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