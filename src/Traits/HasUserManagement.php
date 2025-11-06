<?php

namespace Iquesters\UserManagement\Traits;

use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

trait HasUserManagement
{
    use HasRoles,HasApiTokens;
}