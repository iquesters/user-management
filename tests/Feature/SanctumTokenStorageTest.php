<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Iquesters\Foundation\Support\ConfSecret;
use Iquesters\UserManagement\Helpers\LoginHelper;
use Iquesters\UserManagement\Models\UserMeta;
use Iquesters\UserManagement\Tests\TestCase;

class SanctumTokenStorageTest extends TestCase
{
    private function storedToken(int $userId): ?string
    {
        return UserMeta::where('ref_parent', $userId)->where('meta_key', 'sanctum_plain_token')->value('meta_value');
    }

    public function test_login_stores_the_web_token_encrypted_and_returns_it_decrypted(): void
    {
        $user = $this->makeUser('user');

        $this->assertTrue(LoginHelper::process_login($user));

        $stored = $this->storedToken($user->id);
        $this->assertTrue(ConfSecret::isEncrypted($stored), 'Token is stored in plain text.');

        $token = LoginHelper::get_sanctum_token($user);
        $this->assertNotNull($token);
        $this->assertStringNotContainsString($token, $stored);
        $this->assertSame($token, ConfSecret::decrypt($stored));

        // The token actually authenticates against the API.
        [$id, $plain] = explode('|', $token, 2);
        $this->assertTrue($user->tokens()->whereKey($id)->where('token', hash('sha256', $plain))->exists());
    }

    public function test_legacy_plain_token_still_works(): void
    {
        $user = $this->makeUser('user');
        $plain = $user->createToken('web-app')->plainTextToken;
        $user->setMeta('sanctum_plain_token', $plain);
        UserMeta::where('ref_parent', $user->id)->update(['status' => 'active']);

        $this->assertSame($plain, LoginHelper::get_sanctum_token($user));
    }

    public function test_undecryptable_token_is_treated_as_missing(): void
    {
        $user = $this->makeUser('user');
        $user->createToken('web-app');
        $user->setMeta('sanctum_plain_token', ConfSecret::PREFIX.'garbage');
        UserMeta::where('ref_parent', $user->id)->update(['status' => 'active']);

        $this->assertNull(LoginHelper::get_sanctum_token($user));
        $this->assertNull($this->storedToken($user->id));
    }
}
