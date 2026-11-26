<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Iquesters\UserManagement\Tests\TestCase;

/**
 * Someone sent to sign in from a page that needs it (e.g. an event's
 * registration form from its public page) signs up instead: they land back
 * on that page, not on the dashboard.
 */
class RedirectAfterSignUpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->postJson('/setup', ['app_name' => 'Gate', 'logo' => UploadedFile::fake()->image('logo.png')])->assertOk();
        auth()->logout();
    }

    private function signUp(string $email)
    {
        return $this->postJson('/register', [
            'name' => 'New Person',
            'email' => $email,
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'accept_terms' => true,
        ]);
    }

    public function test_sign_up_returns_to_the_page_they_were_going_to(): void
    {
        $intended = url('/events/01JABCDEFGHJKMNPQRSTVWXYZ0?register=1');

        $this->withSession(['url.intended' => $intended]);
        $this->signUp('new.person@example.test')->assertOk()->assertJsonPath('redirect_url', $intended);
        $this->assertAuthenticated();
    }

    public function test_without_one_sign_up_goes_to_the_dashboard(): void
    {
        $this->signUp('other.person@example.test')->assertOk()->assertJsonPath('redirect_url', route('dashboard', absolute: false));
    }
}
