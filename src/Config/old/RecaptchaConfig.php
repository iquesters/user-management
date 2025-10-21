<?php

namespace Iquesters\UserManagement\Config;

use Iquesters\Foundation\Support\BaseConfig;

class RecaptchaConfig
{
    public bool $enabled;
    public ?string $site_key;
    public ?string $secret_key;

    public function __construct(BaseConfig $config, array $data = [])
    {
        // Use tilde notation for nested keys
        $this->enabled    = (bool) ($config->get(UserManagementKeys::RECAPTCHA_ENABLED) ?? $data['enabled'] ?? true);
        $this->site_key   = $config->get(UserManagementKeys::RECAPTCHA_SITE_KEY) ?? $data['site_key'] ?? null;
        $this->secret_key = $config->get(UserManagementKeys::RECAPTCHA_SECRET_KEY) ?? $data['secret_key'] ?? null;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function toArray(): array
    {
        return [
            'enabled'    => $this->enabled,
            'site_key'   => $this->site_key,
            'secret_key' => $this->secret_key,
        ];
    }
}