<?php

namespace Iquesters\UserManagement\Config;

use Iquesters\Foundation\Support\BaseConf;

class RegistrationFieldsConf extends BaseConf
{
    protected ?string $identifier = 'registration_fields';

    /** @var RegistrationFieldConf[] */
    protected array $fields;

    protected function prepareDefault(BaseConf $default_values)
    {
        $default_values->fields = [];
    }
}
