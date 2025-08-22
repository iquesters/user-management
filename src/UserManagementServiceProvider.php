<?php

namespace Iquesters\UserManagement;

use Illuminate\Support\ServiceProvider;

class UserManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge default config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/user-management.php',
            'usermanagement'
        );
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/auth.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'usermanagement');

        // Publish config + layout
        $this->publishes([
            __DIR__ . '/../config/user-management.php' => config_path('user-management.php'),
            __DIR__ . '/../resources/views/layouts/package.blade.php' => resource_path('views/vendor/usermanagement/layouts/package.blade.php'),
        ], 'user-management-config');
    }
}