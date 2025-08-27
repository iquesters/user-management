<?php

namespace Iquesters\UserManagement;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

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
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load SPATIE migrations (no publish needed)
        $this->loadMigrationsFrom(
            base_path('vendor/spatie/laravel-permission/database/migrations')
        );

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'usermanagement');

        // Register asset route to serve package files without publishing
        $this->registerAssetRoute();

        // Publish config + layout
        $this->publishes([
            __DIR__ . '/../config/user-management.php' => config_path('user-management.php'),
            __DIR__ . '/../resources/views/layouts/package.blade.php' => resource_path('views/vendor/usermanagement/layouts/package.blade.php'),
        ], 'user-management-config');

        // Publish assets (logo, etc.) - Optional for customization
        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/usermanagement'),
        ], 'user-management-assets');
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
            ];

            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

            // Set appropriate cache headers
            $cacheControl = in_array($extension, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico'])
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
        $customLogo = config('usermanagement.logo');

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