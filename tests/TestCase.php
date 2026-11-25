<?php

namespace Iquesters\UserManagement\Tests;

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\ServiceProvider as BreadcrumbsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Iquesters\Foundation\FoundationServiceProvider;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserInterface\UserInterfaceServiceProvider;
use Iquesters\UserManagement\Tests\Fixtures\User;
use Iquesters\UserManagement\UserManagementServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use Laravel\Socialite\SocialiteServiceProvider;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionServiceProvider;

#[WithMigration]
abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            PermissionServiceProvider::class,
            SanctumServiceProvider::class,
            SocialiteServiceProvider::class,
            BreadcrumbsServiceProvider::class,
            FoundationServiceProvider::class,
            UserInterfaceServiceProvider::class,
            UserManagementServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return ['Breadcrumbs' => Breadcrumbs::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('permission.cache.store', 'array');
        // In-memory cache, so cached state (e.g. SetupState's "configured" flag)
        // never leaks from one test into the next.
        $app['config']->set('cache.default', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Sanctum ships its migration unregistered. A fresh install (CI) has no published
        // copy; a local one may (the install step publishes it), so load it only when missing.
        if (! glob(database_path('migrations/*_create_personal_access_tokens_table.php'))) {
            $sanctum = dirname((new \ReflectionClass(SanctumServiceProvider::class))->getFileName(), 2);
            $this->loadMigrationsFrom($sanctum.'/database/migrations');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The app boots (and reads settings into the cache) before the test's
        // database transaction starts, so a value an earlier test saved could
        // be cached here although its row was rolled back. Start clean.
        Cache::flush();
        ConfProvider::forget();
    }

    protected function makeUser(?string $role = null, array $attributes = []): User
    {
        $user = User::create($attributes + [
            'uid' => (string) Str::ulid(),
            'name' => 'Test '.Str::random(6),
            'email' => Str::random(10).'@example.test',
            'password' => bcrypt(Str::random(20)),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        if ($role) {
            Role::findOrCreate($role, 'web');
            $user->assignRole($role);
        }

        return $user;
    }
}
