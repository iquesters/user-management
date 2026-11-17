<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\Settings\SettingsStore;
use Iquesters\UserManagement\Database\Seeders\UserManagementSeeder;
use Iquesters\UserManagement\Tests\TestCase;

class SetupTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (glob(public_path('img/logo_acme*')) ?: [] as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    public function test_schema_form_submit_saves_setup_and_returns_redirect(): void
    {
        $response = $this->postJson('/setup', [
            'app_name' => 'Acme',
            'logo' => UploadedFile::fake()->image('logo.png', 64, 64),
            'description' => 'Alumni portal',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect_url', '/register');

        $this->assertFileExists(public_path('img/logo_acme.png'));

        // Saved through the typed user-interface settings (module_settings).
        $stored = SettingsStore::get(Module::USER_INFE);
        $this->assertSame('Acme', $stored['app_name']);
        $this->assertSame('img/logo_acme.png', $stored['logo']);
        $this->assertSame('Alumni portal', $stored['app_description']);
    }

    public function test_logo_extension_comes_from_content_not_the_client_name(): void
    {
        // Real PNG bytes under a misleading name (fake() would derive the MIME type from the name).
        $this->postJson('/setup', [
            'app_name' => 'Acme Ext',
            'logo' => $this->pngUpload('logo.gif'),
        ])->assertOk();

        $this->assertFileExists(public_path('img/logo_acme-ext.png'));
        $this->assertFileDoesNotExist(public_path('img/logo_acme-ext.gif'));
    }

    public function test_php_named_upload_is_rejected(): void
    {
        $this->postJson('/setup', [
            'app_name' => 'Acme Php',
            'logo' => $this->pngUpload('shell.php'),
        ])->assertStatus(422)->assertJsonValidationErrors(['logo']);

        $this->assertSame([], glob(public_path('img/logo_acme-php*')) ?: []);
    }

    private function pngUpload(string $clientName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'logo');
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));

        return new UploadedFile($path, $clientName, null, null, true);
    }

    public function test_validation_errors_are_returned_as_json(): void
    {
        $this->postJson('/setup', ['app_name' => '', 'logo' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain')])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['app_name', 'logo']);
    }

    public function test_setup_cannot_be_submitted_after_a_super_admin_exists(): void
    {
        $this->makeUser('super-admin');

        $this->postJson('/setup', [
            'app_name' => 'Hijack',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertForbidden();

        $this->get('/setup')->assertRedirect(route('register'));
        $this->assertFileDoesNotExist(public_path('img/logo_hijack.png'));
    }

    public function test_seeder_provides_the_setup_form_schema(): void
    {
        $this->seed(UserManagementSeeder::class);

        $schema = json_decode(DB::table('form_schemas')->where('slug', 'setup-form')->value('schema'), true);

        $this->assertSame('/setup', $schema['endpoint']);
        $this->assertSame(['app_name', 'logo', 'description'], array_column($schema['fields'], 'id'));

        $this->getJson('/api/noauth/form/setup-form')->assertOk()->assertJsonPath('success', true);
    }
}
