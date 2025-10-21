<?php

namespace Iquesters\UserManagement\Config;

class UserManagementKeys
{
    // Layouts
    public const AUTH_LAYOUT = 'AUTH_LAYOUT';
    public const APP_LAYOUT  = 'APP_LAYOUT';

    // Logo
    public const LOGO = 'LOGO';

    // reCAPTCHA (nested keys use ~ separator)
    public const RECAPTCHA = 'RECAPTCHA';
    public const RECAPTCHA_ENABLED     = 'RECAPTCHA~ENABLED';
    public const RECAPTCHA_SITE_KEY    = 'RECAPTCHA~SITE_KEY';
    public const RECAPTCHA_SECRET_KEY  = 'RECAPTCHA~SECRET_KEY';

    // Social login (nested keys use ~ separator)
    public const SOCIAL_LOGINS = 'SOCIAL_LOGINS';
    public const SOCIAL_LOGINS_ENABLED  = 'SOCIAL_LOGINS~ENABLED';

    // Provider-specific (nested under SOCIAL_LOGINS)
    public const SOCIAL_LOGINS_PROVIDERS_GOOGLE_ENABLED = 'SOCIAL_LOGINS~PROVIDERS~GOOGLE~ENABLED';
    public const SOCIAL_LOGINS_PROVIDERS_GOOGLE_CLIENT_ID = 'SOCIAL_LOGINS~PROVIDERS~GOOGLE~CONFIG~CLIENT_ID';
    public const SOCIAL_LOGINS_PROVIDERS_GOOGLE_CLIENT_SECRET = 'SOCIAL_LOGINS~PROVIDERS~GOOGLE~CONFIG~CLIENT_SECRET';
    public const SOCIAL_LOGINS_PROVIDERS_GOOGLE_REDIRECT = 'SOCIAL_LOGINS~PROVIDERS~GOOGLE~CONFIG~REDIRECT';

    // Default auth
    public const DEFAULT_AUTH_ROUTE    = 'DEFAULT_AUTH_ROUTE';
    public const DEFAULT_USER_ROLE     = 'DEFAULT_USER_ROLE';

    // Organisation
    public const ORGANISATION_NEEDED   = 'ORGANISATION_NEEDED';
}