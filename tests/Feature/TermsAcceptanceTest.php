<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Models\UserMeta;
use Iquesters\UserManagement\Support\Terms;
use Iquesters\UserManagement\Tests\TestCase;

/** Bug #9: people agree to the Terms and Privacy policy once, and again when the version changes. */
class TermsAcceptanceTest extends TestCase
{
    public function test_someone_who_has_not_agreed_is_asked_once_then_sent_on(): void
    {
        $user = $this->makeUser('user');
        UserMeta::query()->where('ref_parent', $user->id)->delete(); // e.g. a Google sign-up

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('terms.accept'));
        $this->get(route('terms.accept'))->assertOk();
        // JSON calls and the legal pages aren't blocked.
        $this->getJson('/dashboard')->assertStatus(200);

        $this->postJson(route('terms.accept.store'), [])->assertStatus(422)->assertJsonValidationErrors('accept_terms');
        $this->postJson(route('terms.accept.store'), ['accept_terms' => '1'])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Terms::hasAccepted($user));
        $this->get('/dashboard')->assertOk();
    }

    public function test_a_new_terms_version_asks_again(): void
    {
        $user = $this->makeUser('user');
        $this->actingAs($user)->get('/dashboard')->assertOk();

        ConfProvider::from(Module::USER_MGMT)->update(['terms_version' => '2027-01-01']);
        ConfProvider::forget();

        $this->get('/dashboard')->assertRedirect(route('terms.accept'));
    }

    public function test_it_can_be_switched_off(): void
    {
        $user = $this->makeUser('user');
        UserMeta::query()->where('ref_parent', $user->id)->delete();
        ConfProvider::from(Module::USER_MGMT)->update(['terms_required' => false]);
        ConfProvider::forget();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }
}
