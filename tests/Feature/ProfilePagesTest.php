<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Iquesters\UserManagement\Database\Seeders\UserManagementSeeder;
use Iquesters\UserManagement\Models\UserMeta;
use Iquesters\UserManagement\Support\SetupState;
use Iquesters\UserManagement\Tests\Fixtures\User;
use Iquesters\UserManagement\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ProfilePagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SetupState::markConfigured();
    }

    private function userWithPassword(string $role = 'user'): User
    {
        return $this->makeUser($role, ['password' => Hash::make('Secret-Passw0rd!')]);
    }

    public function test_profile_and_settings_pages_render_the_profile_schema(): void
    {
        $this->seed(UserManagementSeeder::class);
        $this->actingAs($this->userWithPassword());

        $this->get('/myprofile')->assertOk()
            ->assertSee('id="user-profile"', false)
            ->assertSee('data-form-mode="view"', false);

        $this->get('/settings')->assertOk()
            ->assertSee('id="user-profile"', false)
            ->assertSee('data-form-mode="edit"', false)
            ->assertSee('id="user-password-update"', false)
            ->assertSee('id="user-delete-account"', false);
    }

    public function test_users_without_a_password_get_the_set_password_and_email_confirm_forms(): void
    {
        $user = $this->makeUser('user');
        $user->setMeta('password_generated', '1');
        $this->actingAs($user);

        $this->get('/settings')->assertOk()
            ->assertSee('id="user-password-set"', false)
            ->assertSee('id="user-delete-account-email"', false);
    }

    public function test_seeded_schemas_load_theme_options_from_master_data(): void
    {
        $this->seed(UserManagementSeeder::class);
        $this->actingAs($this->userWithPassword());

        $parent = DB::table('master_data')->insertGetId(['key' => 'theme', 'value' => 'Theme', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        foreach (['darkly' => 'Darkly', 'current_theme' => 'default'] as $key => $value) {
            DB::table('master_data')->insert(['key' => $key, 'value' => $value, 'parent_id' => $parent, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }

        $theme = collect($this->getJson('/api/noauth/form/user-profile')->assertOk()->json('response_schema.data.fields'))
            ->firstWhere('id', 'theme');

        $this->assertSame([['value' => '', 'label' => 'Application default'], ['value' => 'darkly', 'label' => 'Darkly']], $theme['options']);
    }

    public function test_profile_update_answers_the_schema_form_with_json(): void
    {
        $user = $this->userWithPassword();
        $this->actingAs($user);

        $this->patchJson('/profile/update', ['name' => 'New Name', 'theme' => ''])
            ->assertOk()
            ->assertJson(['success' => true, 'redirect_url' => '/settings']);

        $this->assertSame('New Name', $user->fresh()->name);
        $this->patchJson('/profile/update', ['name' => ''])->assertStatus(422)->assertJsonValidationErrors('name');
        $this->patchJson('/profile/update', ['name' => 'X', 'theme' => 'not-a-theme'])->assertStatus(422)->assertJsonValidationErrors('theme');
    }

    public function test_password_update_and_first_password(): void
    {
        $user = $this->userWithPassword();
        $this->actingAs($user);

        $this->putJson('/password', ['current_password' => 'wrong', 'password' => 'N3w-Passw0rd!', 'password_confirmation' => 'N3w-Passw0rd!'])
            ->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->putJson('/password', ['current_password' => 'Secret-Passw0rd!', 'password' => 'N3w-Passw0rd!', 'password_confirmation' => 'N3w-Passw0rd!'])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertTrue(Hash::check('N3w-Passw0rd!', $user->fresh()->password));

        // Signed up without a password: no current password needed.
        $google = $this->makeUser('user');
        $google->setMeta('password_generated', '1');
        $this->actingAs($google);
        $this->putJson('/password', ['password' => 'N3w-Passw0rd!', 'password_confirmation' => 'N3w-Passw0rd!'])->assertOk();
        $this->assertTrue(Hash::check('N3w-Passw0rd!', $google->fresh()->password));
        $this->assertTrue($google->fresh()->hasUsablePassword());
    }

    public function test_delete_account_needs_confirmation_and_keeps_the_last_super_admin(): void
    {
        $admin = $this->userWithPassword('super-admin');
        $this->actingAs($admin);

        // Sent by the schema form as POST + _method=DELETE.
        $this->postJson('/profile', ['_method' => 'DELETE', 'password' => 'wrong'])->assertStatus(422);
        $this->postJson('/profile', ['_method' => 'DELETE', 'password' => 'Secret-Passw0rd!'])
            ->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'only super admin'));
        $this->assertNotNull($admin->fresh());

        $user = $this->userWithPassword();
        $this->actingAs($user);
        $this->postJson('/profile', ['_method' => 'DELETE', 'password' => 'Secret-Passw0rd!'])
            ->assertOk()->assertJson(['success' => true, 'redirect_url' => '/']);
        $this->assertNull($user->fresh());
    }

    public function test_profile_picture_upload_serve_and_remove(): void
    {
        Storage::fake('public');
        $user = $this->userWithPassword();
        $this->actingAs($user);

        $this->post('/media/upload', ['media' => [UploadedFile::fake()->image('me.png', 64, 64)]])->assertRedirect();

        $file = $user->profilePictureFile();
        $this->assertNotNull($file);
        $this->assertSame(route('profile.picture', ['filename' => $file]), $user->avatarUrl());
        $this->get(route('profile.picture', ['filename' => $file]))->assertOk();

        $this->post('/remove-profile-picture')->assertRedirect(route('myprofile'));
        $this->assertNull($user->profilePictureFile());
        $this->assertStringStartsWith('https://placehold.co/', $user->avatarUrl());
        $this->assertSame(1, UserMeta::where('ref_parent', $user->id)->where('meta_key', 'profile_picture')->count());
    }

    public function test_picture_schema_form_upload_is_cropped_square_and_answers_json(): void
    {
        Storage::fake('public');
        $user = $this->userWithPassword();
        $this->actingAs($user);

        $this->post('/profile/picture', ['picture' => UploadedFile::fake()->image('wide.jpg', 600, 400)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['success' => true, 'redirect_url' => '/myprofile']);

        $file = $user->profilePictureFile();
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get('media/'.$file));
        $this->assertSame([400, 400], [$width, $height]);

        $this->post('/profile/picture', ['picture' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('picture');

        $this->postJson('/remove-profile-picture')->assertOk()->assertJson(['success' => true]);
        $this->assertNull($user->profilePictureFile());
    }

    public function test_my_profile_passes_view_only_values_to_the_schema(): void
    {
        $this->seed(UserManagementSeeder::class);
        $this->actingAs($this->userWithPassword('super-admin'));

        $html = $this->get('/myprofile')->assertOk()->getContent();

        $this->assertStringContainsString('&quot;roles&quot;:[&quot;Super Admin&quot;]', $html);
        $this->assertStringContainsString('&quot;member_since&quot;', $html);

        $schema = json_decode(DB::table('form_schemas')->where('slug', 'user-profile')->value('schema'), true);
        $this->assertSame('/settings', $schema['modes']['view']['editUrl']);
        $this->assertSame(['view'], collect($schema['fields'])->firstWhere('id', 'picture')['showIn']);
    }

    public static function badPictureNames(): array
    {
        return [['..%2F..%2F.env'], ['missing.png'], ['a%5Cb.png']];
    }

    #[DataProvider('badPictureNames')]
    public function test_picture_route_only_serves_existing_media_files(string $name): void
    {
        Storage::fake('public');
        $this->actingAs($this->userWithPassword());

        $this->get('/profile-picture/'.$name)->assertNotFound();
    }

    public function test_picture_route_requires_sign_in(): void
    {
        $this->get('/profile-picture/x.png')->assertRedirect(route('login'));
    }
}
