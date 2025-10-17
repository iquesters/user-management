<?php

namespace Iquesters\UserManagement\Config;

class UserManagementKeys
{
    // Layouts
    public const LAYOUT_AUTH = 'LAYOUT_AUTH';
    public const LAYOUT_APP  = 'LAYOUT_APP';

    // Logo
    public const LOGO = 'LOGO';

    // reCAPTCHA
    public const RECAPTCHA_ENABLED   = 'RECAPTCHA_ENABLED';
    public const RECAPTCHA_SITE_KEY  = 'RECAPTCHA_SITE_KEY';
    public const RECAPTCHA_SECRET_KEY = 'RECAPTCHA_SECRET_KEY';

    // Social login
    public const SOCIAL_LOGIN_ENABLED = 'SOCIAL_LOGIN_ENABLED';

    // Provider-specific login toggles
    public const GOOGLE_LOGIN = 'GOOGLE_LOGIN';
    // public const FACEBOOK_LOGIN = 'FACEBOOK_LOGIN';
    // public const GITHUB_LOGIN = 'GITHUB_LOGIN';

    // Google OAuth
    public const GOOGLE_CLIENT_ID     = 'GOOGLE_CLIENT_ID';
    public const GOOGLE_CLIENT_SECRET = 'GOOGLE_CLIENT_SECRET';
    public const GOOGLE_REDIRECT      = 'GOOGLE_REDIRECT';

    // Facebook OAuth
    // public const FACEBOOK_CLIENT_ID     = 'FACEBOOK_CLIENT_ID';
    // public const FACEBOOK_CLIENT_SECRET = 'FACEBOOK_CLIENT_SECRET';
    // public const FACEBOOK_REDIRECT      = 'FACEBOOK_REDIRECT';

    // GitHub OAuth
    // public const GITHUB_CLIENT_ID     = 'GITHUB_CLIENT_ID';
    // public const GITHUB_CLIENT_SECRET = 'GITHUB_CLIENT_SECRET';
    // public const GITHUB_REDIRECT      = 'GITHUB_REDIRECT';

    // Default auth
    public const DEFAULT_AUTH_ROUTE = 'DEFAULT_AUTH_ROUTE';
    
    // Default Role
    public const DEFAULT_USER_ROLE = 'DEFAULT_USER_ROLE';

    // Organisation
    public const ORGANISATION_NEEDED = 'ORGANISATION_NEEDED';
}