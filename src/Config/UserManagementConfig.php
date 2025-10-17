<?php

namespace Iquesters\UserManagement\Config;

use Iquesters\Foundation\Support\BaseConfig;

class UserManagementConfig extends BaseConfig
{
    protected string $prefix = 'USER_MANAGEMENT_';

    protected array $defaults = [
        // Layouts
        'layout_auth' => 'usermanagement::layouts.package',
        'layout_app'  => 'usermanagement::layouts.app',

        // Logo
        'logo' => 'img/logo.png',

        // reCAPTCHA
        'recaptcha' => [
            'enabled' => true,
            'site_key' => null,
            'secret_key' => null,
        ],

        // Social OAuth
        'social_login' => [
            'enabled' => false,
            'providers' => [
                [
                    'provider' => 'google',
                    'enabled' => false,
                    'config' => [
                        'client_id' => null,
                        'client_secret' => null,
                        'redirect' => null,
                    ],
                ],
                // [
                //     'provider' => 'facebook',
                //     'enabled' => false,
                //     'config' => [
                //         'client_id' => null,
                //         'client_secret' => null,
                //         'redirect' => null,
                //     ],
                // ],
                // [
                //     'provider' => 'github',
                //     'enabled' => false,
                //     'config' => [
                //         'client_id' => null,
                //         'client_secret' => null,
                //         'redirect' => null,
                //     ],
                // ],
                // Add more providers as needed
            ],
        ],

        // Default auth
        'default_auth_route' => 'dashboard',
        'default_user_role' => 'user',

        // Organisation
        'organisation_needed' => false,
    ];
}