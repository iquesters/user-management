<?php
namespace Iquesters\UserManagement\Config;
use Iquesters\Foundation\Support\BaseConf;

abstract class OAuthConf extends BaseConf
{
    protected bool $enabled;
    protected ?string $client_id;
    protected ?string $client_secret;
    protected ?string $redirect_url;
    protected array $scopes;

}