<?php

namespace Iquesters\UserManagement\Config;

use Iquesters\Foundation\Support\BaseConf;
use Iquesters\Foundation\Enums\Module;

class UserManagementConf extends BaseConf
{
    // Inherited property of BaseConf, must initialize
    protected ?string $identifier = Module::USER_MGMT;
    
    // properties of this class
    protected string $auth_layout;
    protected string $app_layout;
    protected string $logo;
    protected string $default_user_role;
    protected string $default_auth_route;
    protected string $organisation_needed;
    
    protected RecaptchaConf $recaptcha;
    protected SocialLoginConf $social_login;

    protected function prepareDefault(BaseConf $default_values)
    {
        $default_values->auth_layout = 'usermanagement::layouts.package';
        $default_values->app_layout = 'usermanagement::layouts.app';
        $default_values->logo = 'img/logo.png';
        $default_values->default_user_role = 'user';
        $default_values->default_auth_route = 'dashboard';
        $default_values->organisation_needed = false;

        $default_values->recaptcha = new RecaptchaConf();
        $default_values->recaptcha->prepareDefault($default_values->recaptcha);

        $default_values->social_login = new SocialLoginConf();
        $default_values->social_login->prepareDefault($default_values->social_login);
    }


}