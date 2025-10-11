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
        'enabled' => env('USERMANAGEMENT_RECAPTCHA_ENABLED', true),
        'site_key'   => env('USER_MANAGEMENT_RECAPTCHA_SITE_KEY'),
        'secret_key' => env('USER_MANAGEMENT_RECAPTCHA_SECRET_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google OAuth Configuration
    |--------------------------------------------------------------------------
    |
    | These credentials are required for Google login & One Tap.
    | Set them in your .env file:
    |
    | USER_MANAGEMENT_GOOGLE_CLIENT_ID=xxxx
    | USER_MANAGEMENT_GOOGLE_CLIENT_SECRET=xxxx
    | USER_MANAGEMENT_GOOGLE_REDIRECT_URI=https://your-app.com/auth/google/callback
    |
    */
    'google' => [
        'client_id'     => env('USER_MANAGEMENT_GOOGLE_CLIENT_ID'),
        'client_secret' => env('USER_MANAGEMENT_GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('USER_MANAGEMENT_GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Auth Options
    |--------------------------------------------------------------------------
    |
    | These settings control where the user is redirected after login and
    | what default role new users get when registering via Google login.
    |
    */
    'default_auth_route' => env('USER_MANAGEMENT_DEFAULT_AUTH_ROUTE', 'dashboard'),
    'default_user_role'  => env('USER_MANAGEMENT_DEFAULT_USER_ROLE', 'user'),
];