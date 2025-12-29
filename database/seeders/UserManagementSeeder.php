<?php

namespace Iquesters\UserManagement\Database\Seeders;

use Iquesters\Foundation\Database\Seeders\BaseSeeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder for the User Management module
 *
 * Extends BaseSeeder and provides:
 *   - Module configuration
 *   - Entity definitions with fields and metadata
 *   - Custom logic for test user creation
 */
class UserManagementSeeder extends BaseSeeder
{
    /**
     * Module basic information
     */
    protected string $moduleName = 'user-management';
    protected string $description = 'User Management Module';

    /**
     * Module metadata
     */
    protected array $metas = [
        'module_icon' => 'fas fa-user-gear',
        'module_sidebar_menu' => [
            /*
            |-------------------------------------------------
            | Users
            |-------------------------------------------------
            */
            [
                "icon" => "fas fa-users",
                "label" => "Users",
                "route" => "ui.list",
                "table_schema" => [
                    "slug" => "user-table",
                    "name" => "Users",
                    "description" => "Datatable schema for users",
                    "schema" => [
                        "entity" => "users",
                        "dt-options" => [
                            "columns" => [
                                ["data" => "id", "title" => "ID", "visible" => true],
                                [
                                    "data" => "name",
                                    "title" => "User Name",
                                    "visible" => true,
                                    "link" => true,
                                    "form-schema-uid" => "user-details"
                                ],
                                [
                                    "data" => "email",
                                    "title" => "Email",
                                    "visible" => true
                                ],
                                [
                                    "data" => "meta.registered_at",
                                    "title" => "Registered At",
                                    "visible" => true
                                ],
                                [
                                    "data" => "meta.login_ip_address",
                                    "title" => "Login IP",
                                    "visible" => true
                                ],
                            ],
                            "options" => [
                                "pageLength" => 10,
                                "order" => [[0, "desc"]],
                                "responsive" => true
                            ]
                        ],
                        "default_view_mode" => "inbox"
                    ]
                ]
            ],
            /*
            |-------------------------------------------------
            | Roles
            |-------------------------------------------------
            */
            [
                "icon" => "fas fa-user-shield",
                "label" => "Roles",
                "route" => "ui.list",
                "schema" => [
                    "entity" => "roles",
                    "dt-options" => [
                        "columns" => [
                            ["data" => "id", "title" => "ID", "visible" => true],
                            [
                                "data" => "name",
                                "title" => "Role Name",
                                "visible" => true,
                                "link" => true,
                                "form-schema-uid" => "role-details"
                            ]
                        ],
                        "options" => [
                            "pageLength" => 10,
                            "order" => [[0, "desc"]],
                            "responsive" => true
                        ]
                    ],
                    "default_view_mode" => "inbox"
                ]
            ],
            /*
            |-------------------------------------------------
            | Permissions
            |-------------------------------------------------
            */
            [
                "icon" => "fas fa-shield-alt",
                "label" => "Permissions",
                "route" => "ui.list",
                "schema" => [
                    "entity" => "permissions",
                    "dt-options" => [
                        "columns" => [
                            ["data" => "id", "title" => "ID", "visible" => true],
                            [
                                "data" => "name",
                                "title" => "Permission Name",
                                "visible" => true,
                                "link" => true,
                                "form-schema-uid" => "permission-details"
                            ]
                        ],
                        "options" => [
                            "pageLength" => 10,
                            "order" => [[0, "desc"]],
                            "responsive" => true
                        ]
                    ],
                    "default_view_mode" => "inbox"
                ]
            ]
        ]
    ];

    /**
     * Module permissions
     */
    protected array $permissions = [
        'view-users',
        'create-users',
        'edit-users',
        'delete-users',
        'view-roles',
        'create-roles',
        'edit-roles',
        'delete-roles',
        'view-permissions',
        'create-permissions',
        'edit-permissions',
        'delete-permissions',
    ];

    /**
     * Guard name
     */
    protected string $guardName = 'web';

    /**
     * Entity definitions with fields and metadata
     */
    protected array $entities = [
        'users' => [
            'fields' => [],
            'meta_fields' => [
                'google_id' => [
                    'meta_key' => 'google_id',
                    'type' => 'string',
                    'label' => 'Google ID',
                    'required' => false,
                    'nullable' => true,
                ],
                'logo' => [
                    'meta_key' => 'logo',
                    'type' => 'string',
                    'label' => 'User Logo',
                    'required' => false,
                    'nullable' => true,
                    'input_type' => 'file',
                ],
                'registration_ip_address' => [
                    'meta_key' => 'registration_ip_address',
                    'type' => 'string',
                    'label' => 'Registration IP Address',
                    'required' => false,
                    'display' => false,
                ],
                'registration_user_agent' => [
                    'meta_key' => 'registration_user_agent',
                    'type' => 'string',
                    'label' => 'Registration User Agent',
                    'required' => false,
                    'display' => false,
                ],
                'registration_country' => [
                    'meta_key' => 'registration_country',
                    'type' => 'string',
                    'label' => 'Registration Country',
                    'required' => false,
                    'nullable' => true,
                ],
                'registration_locale' => [
                    'meta_key' => 'registration_locale',
                    'type' => 'string',
                    'label' => 'Registration Locale',
                    'required' => false,
                    'nullable' => true,
                ],
                'registration_timezone' => [
                    'meta_key' => 'registration_timezone',
                    'type' => 'string',
                    'label' => 'Registration Timezone',
                    'required' => false,
                    'nullable' => true,
                ],
                'registered_at' => [
                    'meta_key' => 'registered_at',
                    'type' => 'timestamp',
                    'label' => 'Registered At',
                    'required' => false,
                    'display' => false,
                ],
                'current_login_at' => [
                    'meta_key' => 'current_login_at',
                    'type' => 'timestamp',
                    'label' => 'Current Login At',
                    'required' => false,
                    'display' => false,
                ],
                'last_login_at' => [
                    'meta_key' => 'last_login_at',
                    'type' => 'timestamp',
                    'label' => 'Last Login At',
                    'required' => false,
                    'display' => false,
                ],
                'login_ip_address' => [
                    'meta_key' => 'login_ip_address',
                    'type' => 'string',
                    'label' => 'Login IP Address',
                    'required' => false,
                    'display' => false,
                ],
                'login_user_agent' => [
                    'meta_key' => 'login_user_agent',
                    'type' => 'string',
                    'label' => 'Login User Agent',
                    'required' => false,
                    'display' => false,
                ],
                'session_token' => [
                    'meta_key' => 'session_token',
                    'type' => 'string',
                    'label' => 'Session Token',
                    'required' => false,
                    'display' => false,
                ],
                'login_country' => [
                    'meta_key' => 'login_country',
                    'type' => 'string',
                    'label' => 'Login Country',
                    'required' => false,
                    'nullable' => true,
                ],
                'login_locale' => [
                    'meta_key' => 'login_locale',
                    'type' => 'string',
                    'label' => 'Login Locale',
                    'required' => false,
                    'nullable' => true,
                ],
                'login_timezone' => [
                    'meta_key' => 'login_timezone',
                    'type' => 'string',
                    'label' => 'Login Timezone',
                    'required' => false,
                    'nullable' => true,
                ],
            ],
            'metas' => [],
        ],
        'roles' => [
            'fields' => [],
            'meta_fields' => [],
            'metas' => [],
        ],
        'permissions' => [
            'fields' => [],
            'meta_fields' => [],
            'metas' => [],
        ],
    ];

     /**
     * Implement abstract method from BaseSeeder
     */
    protected function seedCustom(): void
    {
        // Add custom seeding logic here if needed
        // Leave empty if none
    }
}