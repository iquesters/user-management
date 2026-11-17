<?php

namespace Iquesters\UserManagement\Tests\Feature;

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
}
