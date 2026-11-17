<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Illuminate\Support\Str;
use Iquesters\UserManagement\Tests\Fixtures\User;
use Iquesters\UserManagement\Tests\TestCase;

class UserMetaTraitTest extends TestCase
{
    public function test_get_and_set_meta_via_trait(): void
    {
        $user = $this->makeUser();

        $this->assertNull($user->getMeta('theme'));
        $this->assertSame('fallback', $user->getMeta('theme', 'fallback'));

        $user->setMeta('theme', 'dark');
        $user->setMeta('theme', 'light');

        $this->assertSame('light', $user->getMeta('theme'));
        $this->assertSame(1, $user->meta()->where('meta_key', 'theme')->count());
    }

    public function test_users_created_without_a_uid_get_one(): void
    {
        // Like Laravel's stock DatabaseSeeder: no uid, model events disabled.
        $user = User::withoutEvents(fn () => User::create([
            'name' => 'Seeded',
            'email' => 'seeded@example.test',
            'password' => bcrypt('secret-password'),
        ]));

        $this->assertTrue(Str::isUlid($user->uid));
        $this->assertSame($user->uid, User::find($user->id)->uid);

        $given = (string) Str::ulid();
        $this->assertSame($given, $this->makeUser(null, ['uid' => $given])->uid);
    }
}
