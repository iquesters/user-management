<?php

namespace Iquesters\UserManagement\Tests;

use Diglactic\Breadcrumbs\ServiceProvider as BreadcrumbsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Iquesters\Foundation\FoundationServiceProvider;
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
        return ['Breadcrumbs' => \Diglactic\Breadcrumbs\Breadcrumbs::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('permission.cache.store', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        // Sanctum's migration is already registered by the package providers.
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    protected function makeUser(?string $role = null, array $attributes = []): User
    {
        $user = User::create($attributes + [
            'uid' => (string) Str::ulid(),
            'name' => 'Test ' . Str::random(6),
            'email' => Str::random(10) . '@example.test',
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
