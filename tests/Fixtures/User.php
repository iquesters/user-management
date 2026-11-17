<?php

namespace Iquesters\UserManagement\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Iquesters\UserManagement\Traits\HasUserManagement;

class User extends Authenticatable
{
    use HasUserManagement;

    protected $table = 'users';

    protected $guarded = [];

    protected $guard_name = 'web';
}
