<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Tests\TestCase;

class DashboardOrganisationTest extends TestCase
{
    public function test_operator_and_global_admin_cannot_create_an_organisation(): void
    {
        foreach (['user', 'admin'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->postJson('/dashboard/create-organisation', ['name' => 'Denied'])->assertForbidden();
        }
    }

    public function test_create_organisation_fails_gracefully_without_the_organisation_package(): void
    {
        // The test User model has no HasOrganisations trait, as in an app
        // without iquesters/organisation.
        $this->actingAs($this->makeUser('super-admin'))
            ->from('/dashboard')
            ->post('/dashboard/create-organisation', ['name' => 'Acme'])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('error', 'Organisations are not available in this application.');
    }

    public function test_only_platform_admins_see_the_create_organisation_form(): void
    {
        // Users without an organisation can't create one (Track B): they are told who adds them.
        ConfProvider::from(Module::USER_MGMT)->organisation_needed = true;
        $this->actingAs($this->makeUser('user'))->get('/dashboard')->assertOk()
            ->assertSee('Ask your administrator to add you to one.')
            ->assertDontSee('Create Organisation');
    }
}
