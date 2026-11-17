<?php

namespace Iquesters\UserManagement\Install;

use Iquesters\Foundation\Support\InstallPublisher;
use Iquesters\Foundation\Support\UserModelPatcher;
use Iquesters\UserManagement\Traits\HasUserManagement;
use Laravel\Sanctum\SanctumServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

/**
 * One-time setup run on `package:discover` (composer install/update).
 * Every step is idempotent, so running it on each update is safe.
 */
final class InstallSteps
{
    public static function run(): void
    {
        $messages = [];

        // Roles & permissions (spatie/laravel-permission)
        if (class_exists(PermissionServiceProvider::class)) {
            $messages[] = InstallPublisher::migration('permission-migrations', '_create_permission_tables.php', 'permissions');
            $messages[] = InstallPublisher::config('permission-config', 'permission.php');
        } else {
            $messages[] = 'spatie/laravel-permission is not installed: composer require spatie/laravel-permission';
        }

        // API tokens (laravel/sanctum)
        if (class_exists(SanctumServiceProvider::class)) {
            $messages[] = InstallPublisher::migration('sanctum-migrations', '_create_personal_access_tokens_table.php', 'personal_access_tokens');
            $messages[] = InstallPublisher::config('sanctum-config', 'sanctum.php');
        }

        // The app's user model: roles/tokens trait + fillable columns set at registration.
        foreach (UserModelPatcher::ensure([HasUserManagement::class], ['uid', 'phone', 'status']) as $change) {
            $messages[] = "User model: {$change}";
        }

        foreach (array_filter($messages) as $message) {
            echo "👤 user-management: {$message}".PHP_EOL;
        }
    }
}
