<?php

namespace Iquesters\UserManagement\Database\Seeders;

use Iquesters\Foundation\Database\Seeders\BaseModuleSeeder;

class UserManagementSeeder extends BaseModuleSeeder
{
    protected string $moduleName = 'user-management';
    protected string $description = 'user-management module';
    protected array $metas = [
        'module_icon' => 'fas fa-user-gear',
        'module_sidebar_menu' => [
            [
                "icon" => "fas fa-users",
                "label" => "Users",
                "route" => "users.index",
            ],
            [
                "icon" => "fas fa-user-shield",
                "label" => "Roles",
                "route" => "roles.index",
            ],
            [
                "icon" => "fas fa-shield-alt",
                "label" => "Permissions",
                "route" => "permissions.index",
            ]
        ]
    ];
}