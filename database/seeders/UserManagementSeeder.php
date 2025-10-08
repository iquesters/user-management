<?php

namespace Iquesters\UserManagement\Database\Seeders;

use Iquesters\Foundation\Database\Seeders\BaseModuleSeeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder for the User Management module
 *
 * Handles:
 *   - Module metadata
 *   - Module permissions
 *   - Test super-admin user creation
 */
class UserManagementSeeder extends BaseModuleSeeder
{
    protected string $moduleName = 'user-management';
    protected string $description = 'User Management Module';

    protected array $metas = [
        'module_icon' => 'fas fa-user-gear',
        'module_sidebar_menu' => [
            ["icon" => "fas fa-users", "label" => "Users", "route" => "users.index"],
            ["icon" => "fas fa-user-shield", "label" => "Roles", "route" => "roles.index"],
            ["icon" => "fas fa-shield-alt", "label" => "Permissions", "route" => "permissions.index"]
        ]
    ];

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

    protected string $guardName = 'web';

    /**
     * Run the seeder
     */
    public function run(): void
    {
        // 1️⃣ Handle module metadata + permissions + super-admin
        parent::run();

        // 2️⃣ Create a test super-admin user
        $user = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Qwer@1234'), // ⚠️ Change in production
            ]
        );
        $user->assignRole('super-admin');

        if (app()->runningInConsole()) {
            $this->command->info("✅ Test super-admin user created/updated.");
        }
    }
}