<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Layout Configuration
    |--------------------------------------------------------------------------
    |
    | By default, the package uses its own layout (usermanagement::layouts.package).
    | You may override this by publishing this config file and/or setting env.
    |
    */
    'layout_auth' => env('USER_MANAGEMENT_AUTH_LAYOUT', 'usermanagement::layouts.package'),
    'layout_app' => env('USER_MANAGEMENT_APP_LAYOUT', 'usermanagement::layouts.app'),

    /*
    |--------------------------------------------------------------------------
    | Logo Configuration
    |--------------------------------------------------------------------------
    |
    | The path or URL of the logo to be displayed on auth pages.
    | You can use:
    | - Full URL: 'https://example.com/logo.png'
    | - Absolute path: '/images/logo.png'
    | - Package asset: 'img/logo.png' (will be served via package route)
    | - Package namespace: 'usermanagement::img.logo.png'
    |
    */
    'logo' => env('USER_MANAGEMENT_LOGO', 'img/logo.png'),

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Configuration
    |--------------------------------------------------------------------------
    */
    'recaptcha' => [
        'site_key'   => env('USER_MANAGEMENT_RECAPTCHA_SITE_KEY'),
        'secret_key' => env('USER_MANAGEMENT_RECAPTCHA_SECRET_KEY'),
    ],
];