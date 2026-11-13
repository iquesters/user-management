<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Iquesters\UserManagement\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AdminRoutesTest extends TestCase
{
    public static function adminPages(): array
    {
        return [['/admin/dashboard'], ['/admin/users'], ['/admin/roles'], ['/admin/permissions'], ['/admin/users/create']];
    }

    #[DataProvider('adminPages')]
    public function test_guest_is_sent_to_login(string $uri): void
    {
        $this->get($uri)->assertRedirect(route('login'));
    }

    #[DataProvider('adminPages')]
    public function test_non_admin_is_forbidden(string $uri): void
    {
        $this->actingAs($this->makeUser('user'))->get($uri)->assertForbidden();
    }

    #[DataProvider('adminPages')]
    public function test_super_admin_is_allowed(string $uri): void
    {
        $response = $this->actingAs($this->makeUser('super-admin'))->get($uri);

        $this->assertNotContains($response->status(), [401, 403], "Admin was denied {$uri}.");
    }

    public function test_non_admin_cannot_create_a_role_or_user(): void
    {
        $user = $this->makeUser('user');

        $this->actingAs($user)->post('/admin/roles', ['name' => 'hacker'])->assertForbidden();
        $this->actingAs($user)->post('/admin/users', ['name' => 'x', 'email' => 'x@example.test'])->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'hacker']);
    }

    public function test_all_admin_routes_carry_ensure_admin(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'admin/'));

        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $this->assertContains('ensure.admin', $route->gatherMiddleware(), "Route [{$route->uri()}] is missing ensure.admin.");
        }
    }
}
