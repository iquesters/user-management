<?php

namespace Iquesters\UserManagement\Config;

use Iquesters\Foundation\Support\BaseConf;

class RecaptchaConf extends BaseConf
{
    protected ?string $identifier = 'recaptcha';
    
    protected bool $enabled;
    protected ?string $site_key;
    protected ?string $secret_key;
    
    protected function prepareDefault(BaseConf $default_values)
    {
        $default_values->enabled = false;
        $default_values->site_key = 'abc111';
        $default_values->secret_key = 'ppp';
    }

}