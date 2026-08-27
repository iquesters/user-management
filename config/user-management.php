<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Runtime Configuration Source
    |--------------------------------------------------------------------------
    |
    | This package reads runtime auth settings from the DB-backed
    | `master_data` / `master_data_metas` tree through `ConfProvider`.
    |
    | Seed defaults into that tree with `php artisan user-management:seed`,
    | then manage live values through the DB-backed config workflow used by
    | your app. The values in this file are publish-time references only.
    |
    */

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
    | Sign-In Flow
    |--------------------------------------------------------------------------
    |
    | Reference only: the runtime `signin_flow` value is loaded from the
    | DB-backed config tree seeded by `php artisan user-management:seed`.
    |
    */
    'signin_flow' => env('USER_MANAGEMENT_SIGNIN_FLOW', 'classic'),

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
    'social_login' => [
        'enabled' => env('USERMANAGEMENT_SOCIAL_LOGIN', false),
        'providers' => [
            [
                'provider' => 'google',
                'enabled' => env('USERMANAGEMENT_GOOGLE_LOGIN', false),
                'config' => [
                    'client_id'     => env('USER_MANAGEMENT_GOOGLE_CLIENT_ID'),
                    'client_secret' => env('USER_MANAGEMENT_GOOGLE_CLIENT_SECRET'),
                    'redirect'      => env('USER_MANAGEMENT_GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
                ]
            ],
            
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp OTP Login Configuration
    |--------------------------------------------------------------------------
    |
    | This powers phone number + OTP login delivered over WhatsApp. The default
    | provider is `fake` so the flow can be wired locally before live provider
    | credentials are introduced. Runtime values still come from the DB-backed
    | config tree, not directly from these `.env` mirrors.
    |
    */
    'whatsapp_login' => [
        'enabled' => env('USERMANAGEMENT_WHATSAPP_LOGIN', false),
        'delivery_provider' => env('USERMANAGEMENT_WHATSAPP_PROVIDER', 'fake'),
        'graph_version' => env('USERMANAGEMENT_WHATSAPP_GRAPH_VERSION', 'v23.0'),
        'phone_number_id' => env('USERMANAGEMENT_WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('USERMANAGEMENT_WHATSAPP_ACCESS_TOKEN'),
        'verify_template_name' => env('USERMANAGEMENT_WHATSAPP_TEMPLATE', 'login_verification'),
        'template_language_code' => env('USERMANAGEMENT_WHATSAPP_TEMPLATE_LANGUAGE_CODE', 'en_IN'),
        'otp_length' => (int) env('USERMANAGEMENT_WHATSAPP_OTP_LENGTH', 6),
        'otp_ttl_minutes' => (int) env('USERMANAGEMENT_WHATSAPP_OTP_TTL_MINUTES', 10),
        'max_attempts' => (int) env('USERMANAGEMENT_WHATSAPP_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => (int) env('USERMANAGEMENT_WHATSAPP_RESEND_COOLDOWN_SECONDS', 60),
        'max_send_per_hour' => (int) env('USERMANAGEMENT_WHATSAPP_MAX_SEND_PER_HOUR', 5),
        'max_verify_failures_per_window' => (int) env('USERMANAGEMENT_WHATSAPP_MAX_VERIFY_FAILURES_PER_WINDOW', 10),
        'max_global_sends_per_hour' => (int) env('USERMANAGEMENT_WHATSAPP_MAX_GLOBAL_SENDS_PER_HOUR', 250),
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

    /*
    |--------------------------------------------------------------------------
    | Configurable Registration Fields
    |--------------------------------------------------------------------------
    |
    | These fields are collected after OTP verification in the unified flow and
    | are persisted through user meta.
    |
    */
    'registration_fields' => [
        'fields' => [],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Need of Organisation 
    |--------------------------------------------------------------------------
    |
    | These settings control whether organisation creation and assigned to user
    | is needed or not.
    |
    */
    'organisation_needed' => env('ORGANISATION_NEEDED', false),
];
