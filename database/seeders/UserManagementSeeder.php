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
                'phone' => [
                    'name' => 'phone',
                    'type' => 'string',
                    'label' => 'Phone Number',
                    'required' => false,
                    'nullable' => true,
                    'input_type' => 'tel',
                ],
                'password' => [
                    'name' => 'password',
                    'type' => 'string',
                    'label' => 'Password',
                    'required' => true,
                    'input_type' => 'password',
                    'hidden' => true,
                ],
                'status' => [
                    'name' => 'status',
                    'type' => 'string',
                    'label' => 'Status',
                    'required' => true,
                    'default' => 'active',
                    'input_type' => 'select',
                    'options' => ['active', 'inactive', 'suspended'],
                ],
                'email_verified_at' => [
                    'name' => 'email_verified_at',
                    'type' => 'timestamp',
                    'label' => 'Email Verified At',
                    'nullable' => true,
                    'display' => false,
                ],
                'phone_verified_at' => [
                    'name' => 'phone_verified_at',
                    'type' => 'timestamp',
                    'label' => 'Phone Verified At',
                    'nullable' => true,
                    'display' => false,
                ],
            ],
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
            'meta_fields' => [],
            'metas' => [],
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
            'meta_fields' => [],
            'metas' => [],
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
                'uid' => Str::ulid(),
                'name' => 'Super Admin',
                'password' => Hash::make('Qwer@1234'), // ⚠️ Change in production
                'status' => 'active',
            ]
        );

        $user->assignRole('super-admin');

        if (app()->runningInConsole()) {
            echo "✅ Test super-admin user created/updated.\n";
        }
    }
}