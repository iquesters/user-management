<?php

namespace Iquesters\UserManagement\Config;

use Iquesters\Foundation\Support\BaseConf;

class RegistrationFieldConf extends BaseConf
{
    protected ?string $identifier = null;
    protected bool $enabled;
    protected bool $required;
    protected string $field_type;
    protected ?string $label;
    protected ?string $default_value;

    protected function prepareDefault(BaseConf $default_values)
    {
        $default_values->enabled = false;
        $default_values->required = false;
        $default_values->field_type = 'text';
        $default_values->label = null;
        $default_values->default_value = null;
    }
}
