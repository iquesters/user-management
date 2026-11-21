<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Iquesters\UserManagement\Tests\TestCase;

class PasswordCredentialRevocationTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../database/security');
    }

    public function test_web_password_change_revokes_legacy_and_refresh_sessions(): void
    {
        $user = $this->makeUser('user', ['password' => Hash::make('old-test-password')]);
        $user->createToken('operator-inbox');
        $user->createToken('knorai-test-device');
        DB::table('account_sessions')->insert(['id' => 'test-family', 'user_id' => $user->id]);
        $this->actingAs($user)->putJson('/password', [
            'current_password' => 'wrong', 'password' => 'new-test-password', 'password_confirmation' => 'new-test-password',
        ])->assertUnprocessable();
        $this->assertSame(2, $user->tokens()->count());
        $this->assertNull(DB::table('account_sessions')->value('revoked_at'));
        $this->putJson('/password', [
            'current_password' => 'old-test-password', 'password' => 'new-test-password', 'password_confirmation' => 'new-test-password',
        ])->assertOk();
        $this->assertSame(0, $user->tokens()->count());
        $this->assertNotNull(DB::table('account_sessions')->value('revoked_at'));
        $this->assertTrue(Hash::check('new-test-password', $user->fresh()->password));
        $this->assertDatabaseHas('account_security_audit', ['user_id' => $user->id, 'event' => 'password_changed_legacy']);
    }

    public function test_admin_reset_writer_is_atomic_when_revocation_fails(): void
    {
        $user = $this->makeUser('user', ['password' => Hash::make('old-test-password')]);
        $user->createToken('operator-inbox');
        DB::table('account_sessions')->insert(['id' => 'test-family', 'user_id' => $user->id]);
        DB::listen(function ($query) {
            if (str_starts_with($query->sql, 'update `account_sessions`')) {
                throw new \RuntimeException('injected disposable revocation failure');
            }
        });
        try {
            $user->password = Hash::make('new-test-password');
            $user->save();
            $this->fail('Expected the injected revocation failure');
        } catch (\RuntimeException) {
            $this->assertTrue(Hash::check('old-test-password', $user->fresh()->password));
            $this->assertSame(1, $user->tokens()->count());
        }
    }

    public function test_php_accepts_python_laravel_bcrypt_format(): void
    {
        // Generated with Python bcrypt.gensalt(rounds=4), test-only password.
        $hash = '$2y$04$DyDtLzMsy4ZEeLuqFR/8e.xNz9JRq5vfZTaUHJHY5lR68gW22EMLm';
        $this->assertTrue(Hash::check('test-only', $hash));
    }
}
