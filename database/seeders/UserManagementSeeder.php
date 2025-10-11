<?php

namespace Iquesters\UserManagement\Database\Seeders;

use Iquesters\Foundation\Database\Seeders\BaseSeeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
            ["icon" => "fas fa-users", "label" => "Users", "route" => "users.index"],
            ["icon" => "fas fa-user-shield", "label" => "Roles", "route" => "roles.index"],
            ["icon" => "fas fa-shield-alt", "label" => "Permissions", "route" => "permissions.index"]
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
            'fields' => [
                'name' => [
                    'name' => 'name',
                    'type' => 'string',
                    'label' => 'User Name',
                    'required' => true,
                    'maxlength' => 255,
                    'input_type' => 'text',
                ],
                'email' => [
                    'name' => 'email',
                    'type' => 'string',
                    'label' => 'Email Address',
                    'required' => true,
                    'unique' => true,
                    'input_type' => 'email',
                ],
                'email_verified_at' => [
                    'name' => 'email_verified_at',
                    'type' => 'timestamp',
                    'label' => 'Email Verified At',
                    'nullable' => true,
                    'display' => false,
                ],
                'password' => [
                    'name' => 'password',
                    'type' => 'string',
                    'label' => 'Password',
                    'required' => true,
                    'input_type' => 'password',
                    'hidden' => true,
                ],
                'remember_token' => [
                    'name' => 'remember_token',
                    'type' => 'rememberToken',
                    'label' => 'Remember Token',
                    'display' => false,
                ],
            ],
            'meta_fields' => [
                'google_id' => [
                    'meta_key' => 'google_id',
                ],
                'logo' => [
                    'meta_key' => 'logo',
                ]
            ],
            'metas' => [],
        ],
        'roles' => [
            'fields' => [
                 'name' => [
                    'name' => 'name',
                    'type' => 'string',
                    'label' => 'Role Name',
                    'required' => true,
                    'maxlength' => 255,
                    'input_type' => 'text',
                ],
                'guard_name' => [
                    'name' => 'guard_name',
                    'type' => 'string',
                    'label' => 'Guard Name',
                    'required' => true,
                    'input_type' => 'text',
                ],
            ],
            'metas' => []
        ],
        'permissions' => [
            'fields' => [
                'name' => [
                    'name' => 'name',
                    'type' => 'string',
                    'label' => 'Permission Name',
                    'required' => true,
                    'maxlength' => 255,
                    'input_type' => 'text',
                ],
                'guard_name' => [
                    'name' => 'guard_name',
                    'type' => 'string',
                    'label' => 'Guard Name',
                    'required' => true,
                    'input_type' => 'text',
                ],
            ],
            'metas' => []
        ],
    ];

    /**
     * Custom seeding logic for User Management
     * This runs after all module, entity, and permission seeding
     */
    protected function seedCustom(): void
    {
        $this->createTestSuperAdmin();
    }

    /**
     * Create a test super-admin user
     */
    protected function createTestSuperAdmin(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Qwer@1234'), // ⚠️ Change in production
            ]
        );

        $user->assignRole('super-admin');

        if (app()->runningInConsole()) {
            echo "✅ Test super-admin user created/updated.\n";
        }
    }
}