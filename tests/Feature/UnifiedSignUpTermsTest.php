<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Database\Seeders\UserManagementSeeder;
use Iquesters\UserManagement\Support\Terms;
use Iquesters\UserManagement\Tests\TestCase;

/**
 * Unified sign-in: the Terms and Privacy agreement is asked on the first step
 * (under the email), and recorded when the account is made; afterwards the
 * person lands on the page that asked for sign-in (e.g. an event's form).
 */
class UnifiedSignUpTermsTest extends TestCase
{
    private const TOKEN = 'flow-token-for-a-new-account-0000000000';

    protected function setUp(): void
    {
        parent::setUp();
        $this->postJson('/setup', ['app_name' => 'Gate', 'logo' => UploadedFile::fake()->image('logo.png')])->assertOk();
        auth()->logout();
        ConfProvider::from(Module::USER_MGMT)->update(['signin_flow' => 'unified']);
        ConfProvider::forget();
    }

    /** The state after "new email (agreed or not), code verified", as the identify and verify steps leave it. */
    private function verifiedNewAccount(string $email, bool $agreed, array $session = []): void
    {
        $this->withSession($session + ['user_management.auth_flows.unified_auth' => [self::TOKEN => [
            'status' => 'new',
            'otp_verified' => true,
            'accepted_terms' => $agreed,
            'identifier_type' => 'email',
            'identifier' => $email,
            'delivery_channel' => 'email',
            'masked_identifier' => 'ne****@example.test',
        ]]]);
    }

    public function test_the_first_step_asks_for_the_agreement(): void
    {
        $this->seed(UserManagementSeeder::class);
        $fields = collect(json_decode(DB::table('form_schemas')->where('slug', 'unified-identify-email')->value('schema'), true)['fields']);
        $this->assertTrue($fields->contains('id', 'accept_terms'));
        $this->assertTrue($fields->firstWhere('id', 'accept_terms')['required']);

        // Not ticked: the first step refuses.
        $this->postJson('/auth/identify', ['identifier' => 'someone@example.test'])
            ->assertStatus(422)->assertJsonValidationErrors('accept_terms');
    }

    public function test_the_agreement_given_up_front_is_recorded_when_the_account_is_made(): void
    {
        $this->verifiedNewAccount('new.person@example.test', true);
        $this->postJson('/auth/register/complete', ['flow_token' => self::TOKEN, 'fields' => ['name' => 'New Person']])->assertOk();

        $this->assertAuthenticated();
        $this->assertTrue(Terms::hasAccepted(auth()->user()));
        $this->assertNotNull(auth()->user()->email_verified_at);
    }

    public function test_no_account_without_the_agreement(): void
    {
        $this->verifiedNewAccount('no.agreement@example.test', false);
        $this->postJson('/auth/register/complete', ['flow_token' => self::TOKEN, 'fields' => ['name' => 'X']])
            ->assertStatus(422)->assertJsonValidationErrors('accept_terms');
        $this->assertGuest();
    }

    public function test_it_returns_to_the_page_that_asked_for_sign_in(): void
    {
        $intended = url('/events/01JABCDEFGHJKMNPQRSTVWXYZ0?register=1');
        $this->verifiedNewAccount('event.person@example.test', true, ['url.intended' => $intended]);

        $this->postJson('/auth/register/complete', ['flow_token' => self::TOKEN, 'fields' => ['name' => 'Event Person']])
            ->assertOk()->assertJsonPath('redirect_url', $intended);
    }
}
