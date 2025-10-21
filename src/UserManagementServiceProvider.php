<?php

namespace Iquesters\UserManagement;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Command;
use Iquesters\Foundation\Support\ConfigProvider;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\Foundation\Enums\Module;
use Iquesters\UserManagement\Config\UserManagementConfig;
use Iquesters\UserManagement\Config\UserManagementConf;
use Iquesters\UserManagement\Config\UserManagementKeys;
use Iquesters\UserManagement\Database\Seeders\UserManagementSeeder;

class UserManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge default config
        // $this->mergeConfigFrom(
        //     __DIR__ . '/../config/user-management.php',
        //     'usermanagement'
        // );

        // ConfigProvider::register(Module::USER_MGMT, UserManagementConfig::class);
        ConfProvider::register(Module::NEW_USER_MGMT, UserManagementConf::class);

        $this->registerSeedCommand();
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load your package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'usermanagement');

        // Register asset route to serve package files without publishing
        $this->registerAssetRoute();
        
        if ($this->app->runningInConsole()) {
            $this->commands([
                'command.user-management.seed'
            ]);
        }
        
        // Publish config + layout
        $this->publishes([
            __DIR__ . '/../config/user-management.php' => config_path('user-management.php'),
            __DIR__ . '/../resources/views/layouts/package.blade.php' => resource_path('views/vendor/usermanagement/layouts/package.blade.php'),
        ], 'user-management-config');

        // Publish assets (logo, etc.) - Optional for customization
        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/usermanagement'),
        ], 'user-management-assets');

        // Auto-publish Spatie migrations AND config
        $this->autoPublishSpatieFiles();

        // Map Google config to Socialite dynamically
        if (config('usermanagement.google.client_id')) {
            config([
                'services.google.client_id'     => config('usermanagement.google.client_id'),
                'services.google.client_secret' => config('usermanagement.google.client_secret'),
                'services.google.redirect'      => config('usermanagement.google.redirect'),
            ]);
        }
    }

    protected function registerSeedCommand(): void
    {
        $this->app->singleton('command.user-management.seed', function ($app) {
            return new class extends Command {
                protected $signature = 'user-management:seed';
                protected $description = 'Seed User Management module data';

                public function handle()
                {
                    $this->info('Running User Management Seeder...');
                    $seeder = new UserManagementSeeder();
                    $seeder->setCommand($this);
                    $seeder->run();
                    $this->info('User Management seeding completed!');
                    return 0;
                }
            };
        });
    }

    /**
     * Auto-publish Spatie Permission migrations AND config
     */
    protected function autoPublishSpatieFiles(): void
    {
        // Only run in console environment during package discovery
        if (!$this->app->runningInConsole() || !$this->isDuringPackageDiscovery()) {
            return;
        }

        // Use deferred execution to ensure Spatie package is registered
        $this->app->booted(function () {
            $this->publishSpatieFilesNow();
        });
    }

    /**
     * Check if we're during package discovery
     */
    protected function isDuringPackageDiscovery(): bool
    {
        return isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === 'package:discover';
    }

    /**
     * Actually publish the Spatie migrations AND config using proper Laravel publishing
     */
    protected function publishSpatieFilesNow(): void
    {
        // Check if Spatie's service provider is available
        if (!class_exists(\Spatie\Permission\PermissionServiceProvider::class)) {
            echo "⚠️  Spatie Permission package is not installed." . PHP_EOL;
            echo "📝 Please install it via: composer require spatie/laravel-permission" . PHP_EOL . PHP_EOL;
            return;
        }

        $migrationPath = database_path('migrations');
        $configPath = config_path('permission.php');

        // Check if Spatie migrations are already published
        $spatieFiles = glob($migrationPath . '/*_create_permission_tables.php');
        $configExists = file_exists($configPath);

        // Check if tables already exist in database
        $tablesExist = false;
        try {
            $tablesExist = $this->checkIfTablesExist();
        } catch (\Exception $e) {
            // Database connection might not be available, continue with publishing
        }

        if ($tablesExist) {
            echo "✅ Spatie Permission tables already exist in database." . PHP_EOL;
            return;
        }

        if (!empty($spatieFiles) && $configExists) {
            echo "✅ Spatie Permission migrations and config already published." . PHP_EOL;
            echo "📋 You can now run 'php artisan migrate' to create the permission tables." . PHP_EOL . PHP_EOL;
            return;
        }

        // Use Artisan command to publish Spatie migrations AND config
        try {
            // Publish both migrations and config with a single command
            Artisan::call('vendor:publish', [
                '--provider' => 'Spatie\Permission\PermissionServiceProvider',
                '--force' => true
            ]);

            $output = Artisan::output();

            if (str_contains($output, 'Publishing complete')) {
                echo "✅ Spatie Permission files published successfully!" . PHP_EOL;

                // Check what was actually published
                $newSpatieFiles = glob($migrationPath . '/*_create_permission_tables.php');
                $newConfigExists = file_exists($configPath);

                if (!empty($newSpatieFiles) && $newConfigExists) {
                    echo "✅ Migrations and config both published." . PHP_EOL;
                } elseif (!empty($newSpatieFiles)) {
                    echo "✅ Migrations published, but config may already exist." . PHP_EOL;
                } elseif ($newConfigExists) {
                    echo "✅ Config published, but migrations may already exist." . PHP_EOL;
                }

                echo "📋 You can now run 'php artisan migrate' to create the permission tables." . PHP_EOL . PHP_EOL;
            } else {
                // If the general command didn't work, try specific tags
                $this->publishWithSpecificTags();
            }
        } catch (\Exception $e) {
            echo "⚠️  Could not automatically publish Spatie files: " . $e->getMessage() . PHP_EOL;
            $this->showManualInstructions();
        }
    }

    /**
     * Try publishing with specific tags as fallback
     */
    protected function publishWithSpecificTags(): void
    {
        try {
            $migrationPath = database_path('migrations');
            $configPath = config_path('permission.php');

            $spatieFiles = glob($migrationPath . '/*_create_permission_tables.php');
            $configExists = file_exists($configPath);

            // Publish migrations if needed
            if (empty($spatieFiles)) {
                Artisan::call('vendor:publish', [
                    '--provider' => 'Spatie\Permission\PermissionServiceProvider',
                    '--tag' => 'permission-migrations',
                    '--force' => true
                ]);
                echo "✅ Spatie Permission migrations published!" . PHP_EOL;
            }

            // Publish config if needed
            if (!$configExists) {
                Artisan::call('vendor:publish', [
                    '--provider' => 'Spatie\Permission\PermissionServiceProvider',
                    '--tag' => 'permission-config',
                    '--force' => true
                ]);
                echo "✅ Spatie Permission config published!" . PHP_EOL;
            }

            echo "📋 You can now run 'php artisan migrate' to create the permission tables." . PHP_EOL . PHP_EOL;
        } catch (\Exception $e) {
            echo "⚠️  Could not publish with specific tags: " . $e->getMessage() . PHP_EOL;
            $this->showManualInstructions();
        }
    }

    /**
     * Show manual instructions
     */
    protected function showManualInstructions(): void
    {
        echo "📝 Please run manually:" . PHP_EOL;
        echo "   php artisan vendor:publish --provider=\"Spatie\Permission\PermissionServiceProvider\"" . PHP_EOL;
        echo "   php artisan migrate" . PHP_EOL . PHP_EOL;
    }

    /**
     * Check if permission tables already exist in the database
     */
    protected function checkIfTablesExist(): bool
    {
        try {
            // Check if we can connect to the database and if tables exist
            if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
                return true;
            }
        } catch (\Exception $e) {
            // Database might not be available or configured yet
            // This is normal during initial package installation
        }

        return false;
    }

    /**
     * Register route to serve package assets without publishing
     */
    protected function registerAssetRoute(): void
    {
        Route::get('/vendor/usermanagement/{path}', function ($path) {
            $filePath = __DIR__ . '/../public/' . $path;

            // Check if file exists
            if (!file_exists($filePath)) {
                abort(404);
            }

            // Get MIME type
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'ico' => 'image/x-icon',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf',
                'eot' => 'application/vnd.ms-fontobject',
            ];

            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

            // Set appropriate cache headers
            $cacheControl = in_array($extension, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'])
                ? 'public, max-age=31536000' // 1 year for assets
                : 'no-cache';

            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Cache-Control' => $cacheControl,
            ]);
        })->where('path', '.*')->name('usermanagement.asset');
    }

    /**
     * Get the logo URL - works with both package assets and custom paths
     */
    public static function getLogoUrl(): string
    {
        $customLogo = ConfigProvider::from(Module::USER_MGMT)->get(UserManagementKeys::LOGO);

        // If it's a full URL, return as is
        if (filter_var($customLogo, FILTER_VALIDATE_URL)) {
            return $customLogo;
        }

        // If it's an absolute path (starts with /), return as is
        if (str_starts_with($customLogo, '/')) {
            return $customLogo;
        }

        // If it contains "::" format, convert to path
        if (str_contains($customLogo, '::')) {
            $path = str_replace('::', '/', $customLogo);
            return route('usermanagement.asset', ['path' => $path]);
        }

        // For regular relative paths, use the asset route
        return route('usermanagement.asset', ['path' => ltrim($customLogo, '/')]);
    }

    /**
     * Get the CSS URL for the package
     */
    public static function getCssUrl(string $file = 'css/app.css'): string
    {
        return route('usermanagement.asset', ['path' => $file]);
    }

    /**
     * Get the JS URL for the package
     */
    public static function getJsUrl(string $file = 'js/app.js'): string
    {
        return route('usermanagement.asset', ['path' => $file]);
    }
}