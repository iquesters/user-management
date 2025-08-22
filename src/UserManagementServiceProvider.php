<?php

namespace Iquesters\UserManagement;

use Illuminate\Support\ServiceProvider;

class UserManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/user-management.php', 'usermanagement');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/auth.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/auth', 'usermanagement');

        $this->publishes([
            __DIR__ . '/../config/masterdata.php' => config_path('masterdata.php'),
            __DIR__ . '/../resources/views/layouts/package.blade.php' => resource_path('views/vendor/masterdata/layouts/package.blade.php'),
        ], 'masterdata-config');
    }
}