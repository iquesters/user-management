<?php

namespace Iquesters\UserManagement\Config;

use Iquesters\Foundation\Support\BaseConfig;

class UserManagementConfig extends BaseConfig
{
    protected string $prefix = 'USER_MANAGEMENT_';
    protected string $moduleKey = 'user_mgmt';

    protected array $defaults = [
        // Layouts
        'auth_layout' => 'usermanagement::layouts.package',
        'app_layout'  => 'usermanagement::layouts.app',

        // Logo
        'logo' => 'img/logo.png',

        // reCAPTCHA
        'recaptcha' => [
            'enabled' => true,
            'site_key' => null,
            'secret_key' => null,
        ],

        // Social logins
        'social_logins' => [
            'enabled' => false,
            'providers' => [
                'google' => [
                    'enabled' => false,
                    'config' => [
                        'client_id' => null,
                        'client_secret' => null,
                        'redirect' => null,
                    ],
                ],
            ],
        ],

        // Default values
        'default_auth_route' => 'dashboard',
        'default_user_role' => 'user',
        'organisation_needed' => false,
    ];

    public function get(string $key, $default = null)
    {
        $value = parent::get($key, $default);

        return match (strtolower($key)) {
            'recaptcha'     => new RecaptchaConfig($this, (array) $value),
            'social_logins' => new SocialLoginConfig($this, (array) $value),
            default         => $value,
        };
    }

}