<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Iquesters\Foundation\Models\BusinessEntity;
use Iquesters\UserManagement\Database\Seeders\UserManagementSeeder;
use Iquesters\UserManagement\Tests\Fixtures\User;
use Iquesters\UserManagement\Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

/**
 * The Users screen form (user-details) creates and edits a user with their
 * roles, through foundation's "user-account" business entity (users + roles
 * via Spatie's model_has_roles). No separate list, no new tables.
 */
class UserAccountRolesTest extends TestCase
{
    private const API = '/api/foundation/v1/business-entity';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserManagementSeeder::class);
    }

    public function test_the_seeder_sets_up_an_api_only_business_entity(): void
    {
        $businessEntity = BusinessEntity::where('slug', 'user-account')->firstOrFail();

        $this->assertSame('api', $businessEntity->getMeta('output'));
        // API only: no generated form or list of its own.
        $this->assertSame(0, DB::table('form_schemas')->where('slug', 'business-user-account-form')->where('status', 'active')->count());
        $this->assertSame(0, DB::table('table_schemas')->where('slug', 'business-user-account-table')->where('status', 'active')->count());
        // The Users table keeps opening user-details.
        $this->assertSame('user-details', json_decode(DB::table('table_schemas')->where('slug', 'user-table')->value('schema'), true)['create']['form']);
    }

    public function test_user_details_saves_through_the_business_entity_with_roles(): void
    {
        $form = json_decode(DB::table('form_schemas')->where('slug', 'user-details')->value('schema'), true);
        $fields = collect($form['fields'])->keyBy('id');

        $this->assertSame('business-entity/user-account', $form['entity']);
        $this->assertStringEndsWith('/business-entity/store/user-account', $form['modes']['create']['endpoint']);
        $this->assertSame(['user__name', 'user__email', 'user__phone', 'status', 'roles', 'user__password', 'user__password_confirmation'], $fields->keys()->all());
        $this->assertSame('checkbox', $fields['roles']['type']);
        $this->assertStringEndsWith('/business-entity/options/user-account/roles', $fields['roles']['optionsFrom']['url']);
        $this->assertSame(['view'], $fields['status']['showIn']);
        $this->assertSame('user__password', $fields['user__password_confirmation']['confirms']);
    }

    public function test_an_admin_creates_and_edits_a_user_with_roles(): void
    {
        Sanctum::actingAs($this->makeUser('super-admin'));
        $editor = Role::findOrCreate('editor', 'web');
        $viewer = Role::findOrCreate('viewer', 'web');

        $options = $this->getJson(self::API.'/options/user-account/roles')->assertOk()->json('response_schema.data');
        $this->assertContains('editor', array_column($options, 'label'));

        $record = $this->postJson(self::API.'/store/user-account', [
            'user__name' => 'Asha', 'user__email' => 'asha@example.test', 'user__password' => 'Secret#123',
            'roles' => [(string) $editor->id, (string) $viewer->id],
        ])->assertCreated()->json('response_schema.data');

        $user = User::where('email', 'asha@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Secret#123', $user->password));
        $this->assertTrue($user->hasAllRoles(['editor', 'viewer']));
        $this->assertNull($record['user__password']);
        // References in words, for components: the role names.
        $this->assertEqualsCanonicalizing(['editor', 'viewer'], $record['labels']['roles']);

        // Edit: drop a role; no password sent keeps the current one.
        $this->putJson(self::API."/update/user-account/{$user->uid}", ['user__name' => 'Asha Roy', 'roles' => [(string) $viewer->id]])
            ->assertOk()
            ->assertJsonPath('response_schema.data.roles', [(string) $viewer->id])
            ->assertJsonPath('response_schema.data.labels.roles', ['viewer']);

        $user->refresh();
        $this->assertSame('Asha Roy', $user->name);
        $this->assertSame(['viewer'], $user->getRoleNames()->all());
        $this->assertTrue(Hash::check('Secret#123', $user->password));
    }

    public function test_non_admins_cannot_assign_roles(): void
    {
        Sanctum::actingAs($this->makeUser('user'));
        $editor = Role::findOrCreate('editor', 'web');

        $this->postJson(self::API.'/store/user-account', ['user__name' => 'X', 'user__email' => 'x@example.test', 'user__password' => 'Secret#123', 'roles' => [(string) $editor->id]])
            ->assertForbidden();
        $this->getJson(self::API.'/options/user-account/roles')->assertForbidden();
    }
}
