<?php
namespace Iquesters\UserManagement\Config;
use Iquesters\Foundation\Support\BaseConf;

class SocialLoginConf extends BaseConf
{
    protected bool $enabled;
    
    /** @var OAuthConf[] */
    protected array $o_auth_providers;
    
    protected function prepareDefault(BaseConf $default_values)
    {
        $default_values->enabled = false;
        
        $google = new GoogleConf();
        $google->prepareDefault($google);
        
        $default_values->o_auth_providers = [
            $google
        ];
    }

}