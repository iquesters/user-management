<?php
namespace Iquesters\UserManagement\Config;
use Iquesters\Foundation\Support\BaseConf;

class GoogleConf extends OAuthConf
{
    protected ?string $identifier = 'google';
    
    protected function prepareDefault(BaseConf $default_values)
    {
        $default_values->enabled = true;
        $default_values->client_id = '';
        $default_values->client_secret = '';
        $default_values->redirect_url= '';
        $default_values->scopes = ['email', 'profile'];
        
    }
}