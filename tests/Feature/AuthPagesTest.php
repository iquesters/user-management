<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Iquesters\UserManagement\Tests\TestCase;

class AuthPagesTest extends TestCase
{
    public function test_confirm_password_page_renders(): void
    {
        $this->actingAs($this->makeUser('user'))
            ->get('/confirm-password')
            ->assertOk()
            ->assertSee('confirm your password', false);
    }

    public function test_verify_email_page_renders_for_unverified_user(): void
    {
        $user = $this->makeUser('user', ['email_verified_at' => null]);

        $this->actingAs($user)
            ->get('/verify-email')
            ->assertOk()
            ->assertSee('Resend Verification Email');
    }

    public function test_settings_route_points_to_profile_settings(): void
    {
        $route = app('router')->getRoutes()->getByName('settings');

        $this->assertNotNull($route);
        $this->assertSame('Iquesters\\UserManagement\\Http\\Controllers\\ProfileController@settings', $route->getActionName());
    }
}
