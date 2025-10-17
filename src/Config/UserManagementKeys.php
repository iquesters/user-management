<?php

namespace Iquesters\UserManagement\Config;

class UserManagementKeys
{
    // Layouts
    public const AUTH_LAYOUT = 'AUTH_LAYOUT';
    public const APP_LAYOUT  = 'APP_LAYOUT';

    // Logo
    public const LOGO = 'LOGO';

    // reCAPTCHA
    public const RECAPTCHA_ENABLED     = 'RECAPTCHA_ENABLED';
    public const RECAPTCHA_SITE_KEY    = 'RECAPTCHA_SITE_KEY';
    public const RECAPTCHA_SECRET_KEY  = 'RECAPTCHA_SECRET_KEY';

    // Social login (global)
    public const SOCIAL_LOGIN_ENABLED  = 'SOCIAL_LOGIN_ENABLED';

    // Provider-specific
    public const GOOGLE_LOGIN          = 'GOOGLE_LOGIN';
    public const GOOGLE_CLIENT_ID      = 'GOOGLE_CLIENT_ID';
    public const GOOGLE_CLIENT_SECRET  = 'GOOGLE_CLIENT_SECRET';
    public const GOOGLE_REDIRECT       = 'GOOGLE_REDIRECT';

    // Default auth
    public const DEFAULT_AUTH_ROUTE    = 'DEFAULT_AUTH_ROUTE';
    public const DEFAULT_USER_ROLE     = 'DEFAULT_USER_ROLE';

    // Organisation
    public const ORGANISATION_NEEDED   = 'ORGANISATION_NEEDED';
}