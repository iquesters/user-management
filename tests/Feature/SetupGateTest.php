<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Iquesters\UserManagement\Support\SetupState;
use Iquesters\UserManagement\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SetupGateTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (glob(public_path('img/logo_gate*')) ?: [] as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    public static function guardedPages(): array
    {
        return [['/login'], ['/register'], ['/forgot-password'], ['/auth/unified']];
    }

    #[DataProvider('guardedPages')]
    public function test_auth_pages_redirect_to_setup_until_setup_is_submitted(string $uri): void
    {
        $this->assertFalse(SetupState::isConfigured());

        $this->get($uri)->assertRedirect(route('ui.setup'));
        $this->getJson($uri)->assertStatus(409)->assertJsonPath('redirect_url', '/setup');
    }

    public function test_setup_page_itself_stays_reachable(): void
    {
        $this->get('/setup')->assertOk();
    }

    public function test_auth_pages_open_after_setup_is_submitted(): void
    {
        $this->postJson('/setup', [
            'app_name' => 'Gate',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertOk();

        $this->assertTrue(SetupState::isConfigured());
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_seeded_default_app_name_does_not_count_as_setup(): void
    {
        // user-interface:seed stores every conf default, APP_NAME included.
        $confId = \Illuminate\Support\Facades\DB::table('master_data')->insertGetId([
            'key' => 'user-interface-conf', 'value' => 'user-interface-conf', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('master_data_metas')->insert([
            'ref_parent' => $confId, 'meta_key' => 'APP_NAME', 'meta_value' => 'Iquesters',
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertFalse(SetupState::isConfigured());
        $this->get('/login')->assertRedirect(route('ui.setup'));
    }

    public function test_existing_installs_with_users_count_as_configured(): void
    {
        $this->makeUser('user');

        $this->assertTrue(SetupState::isConfigured());
        $this->get('/login')->assertOk();
    }

    public function test_home_banner_hides_login_and_register_before_setup(): void
    {
        $html = view('userinterface::components.setup-link')->render();

        $this->assertStringContainsString(route('ui.setup'), $html);
        $this->assertStringContainsString('a[href="' . route('login') . '"]', $html);
        $this->assertStringContainsString('a[href="' . route('register') . '"]', $html);
    }

    public function test_home_banner_offers_admin_signup_after_setup(): void
    {
        SetupState::markConfigured();

        $html = view('userinterface::components.setup-link')->render();

        $this->assertStringContainsString('Create admin account', $html);
        $this->assertStringContainsString('a[href="' . route('login') . '"]', $html);
        $this->assertStringNotContainsString('a[href="' . route('register') . '"]', $html);
    }

    public function test_home_banner_disappears_once_a_super_admin_exists(): void
    {
        $this->makeUser('super-admin');

        $this->assertSame('', trim(view('userinterface::components.setup-link')->render()));
    }
}