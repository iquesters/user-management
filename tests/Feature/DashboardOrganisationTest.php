<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Iquesters\UserManagement\Tests\TestCase;

class DashboardOrganisationTest extends TestCase
{
    public function test_create_organisation_fails_gracefully_without_the_organisation_package(): void
    {
        // The test User model has no HasOrganisations trait, as in an app
        // without iquesters/organisation.
        $this->actingAs($this->makeUser('user'))
            ->from('/dashboard')
            ->post('/dashboard/create-organisation', ['name' => 'Acme'])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('error', 'Organisations are not available in this application.');
    }
}
