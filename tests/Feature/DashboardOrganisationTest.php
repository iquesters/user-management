<?php

namespace Iquesters\UserManagement\Tests\Feature;

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
}
