<?php
namespace Iquesters\UserManagement\Config;

class GoogleLoginConfig extends OAuthLoginConfig
{
    public function __construct(UserManagementConfig $config, array $data = [])
    {
        $data = array_merge_recursive([
            'provider' => 'google',
            'config'   => ['scopes' => ['openid', 'profile', 'email']],
        ], $data);

        parent::__construct($config, $data);
    }
}