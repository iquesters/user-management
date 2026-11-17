<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Services\GoogleIdTokenVerifier;
use Iquesters\UserManagement\Support\SafeRedirect;
use Iquesters\UserManagement\Support\SetupState;
use Iquesters\UserManagement\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SafeRedirectTest extends TestCase
{
    public static function rejected(): array
    {
        return [
            'other host' => ['https://evil.example/login'],
            'protocol-relative' => ['//evil.example'],
            'backslash trick' => ['/\\evil.example'],
            'backslash host' => ['https:\\\\evil.example'],
            'javascript' => ['javascript:alert(1)'],
            'data' => ['data:text/html,<script>alert(1)</script>'],
            'lookalike host' => ['http://localhost.evil.example/'],
            'userinfo' => ['http://localhost@evil.example/'],
            'newline' => ["/dash\nboard"],
            'tab in scheme' => ["java\tscript:alert(1)"],
            'other port' => ['http://localhost:8081/dashboard'],
            'empty' => [''],
            'not a string' => [['/dashboard']],
        ];
    }

    #[DataProvider('rejected')]
    public function test_off_site_urls_are_rejected(mixed $url): void
    {
        $this->assertNull(SafeRedirect::url($url));
    }

    public function test_same_site_urls_are_kept(): void
    {
        $this->assertSame('/dashboard?tab=1#x', SafeRedirect::url('/dashboard?tab=1#x'));
        $this->assertSame('http://localhost/organisations/1', SafeRedirect::url('http://localhost/organisations/1'));
        $this->assertSame('HTTP://LOCALHOST/x', SafeRedirect::url('HTTP://LOCALHOST/x'));
    }

    public function test_google_redirect_does_not_store_an_off_site_url(): void
    {
        $this->enableGoogle();

        $this->get('/auth/google/redirect?redirect_url=https://evil.example')->assertRedirect();
        $this->assertNull(session('redirect_url'));

        $this->get('/auth/google/redirect?redirect_url=/organisations')->assertRedirect();
        $this->assertSame('/organisations', session('redirect_url'));
    }

    public function test_one_tap_login_never_redirects_off_site(): void
    {
        $this->enableGoogle();
        $this->fakeVerifiedToken();

        $this->post('/auth/google/onetap', ['credential' => 'token', 'redirect_url' => 'https://evil.example'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        auth()->logout();

        $this->withSession(['redirect_url' => '//evil.example'])
            ->post('/auth/google/onetap', ['credential' => 'token'])
            ->assertRedirect(route('dashboard'));

        auth()->logout();

        $this->post('/auth/google/onetap', ['credential' => 'token', 'redirect_url' => '/organisations'])
            ->assertRedirect('/organisations');
    }

    private function enableGoogle(): void
    {
        SetupState::markConfigured();

        ConfProvider::from(Module::USER_MGMT)->update([
            'social_login' => [
                'enabled' => true,
                'o_auth_providers' => [
                    'google' => [
                        'enabled' => true,
                        'one_tap_enabled' => true,
                        'client_id' => 'test-client-id',
                        'client_secret' => 'test-secret',
                        'redirect_url' => 'http://localhost/auth/google/callback',
                    ],
                ],
            ],
        ]);
    }

    private function fakeVerifiedToken(): void
    {
        $this->app->instance(GoogleIdTokenVerifier::class, new class extends GoogleIdTokenVerifier
        {
            public function __construct() {}

            public function verify(?string $idToken, string $clientId): ?array
            {
                return ['sub' => 'google-123', 'email' => 'onetap@example.test', 'name' => 'One Tap', 'email_verified' => true];
            }
        });
    }
}
