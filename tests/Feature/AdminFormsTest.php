<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Iquesters\Foundation\Models\Module;
use Iquesters\UserInterface\Support\RecordComponents;
use Iquesters\UserManagement\Database\Seeders\UserManagementSeeder;
use Iquesters\UserManagement\Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminFormsTest extends TestCase
{
    public function test_seeder_adds_detail_forms_for_the_admin_tables(): void
    {
        $this->seed(UserManagementSeeder::class);

        $schemas = DB::table('form_schemas')->whereIn('slug', ['user-details', 'role-details', 'permission-details'])->pluck('schema', 'slug');
        $this->assertCount(3, $schemas);

        $user = json_decode($schemas['user-details'], true);
        $this->assertSame(['user__name', 'user__email', 'user__phone', 'status', 'roles', 'user__password', 'user__password_confirmation'], array_column($user['fields'], 'id'));
        // Password: create + edit (not view); required on create, blank on edit keeps the current one.
        $password = collect($user['fields'])->firstWhere('id', 'user__password');
        $this->assertSame(['create', 'edit'], $password['showIn']);
        $this->assertSame(['create'], $password['requiredIn']);
        $this->assertTrue($password['omitIfEmpty']);
        $this->assertSame('end', $user['actions'][0]['align']);
        $this->assertTrue($user['modes']['edit']['allowCancel']);

        $role = json_decode($schemas['role-details'], true);
        $this->assertSame('/admin/roles/{uid}/details', $role['dataEndpoint']);

        $permission = json_decode($schemas['permission-details'], true);
        $this->assertFalse($permission['modes']['view']['allowEdit']);
    }

    public function test_only_admin_roles_see_the_module(): void
    {
        $this->seed(UserManagementSeeder::class);

        $roles = Module::where('name', 'user-management')->first()->getAssignedRoles()->pluck('name')->sort()->values()->all();

        $this->assertSame(['admin', 'super-admin'], $roles);
    }

    public function test_role_endpoints_are_admin_only(): void
    {
        $role = Role::findOrCreate('editor', 'web');
        $this->actingAs($this->makeUser('user'));

        $this->getJson("/admin/roles/{$role->id}/details")->assertForbidden();
        $this->putJson("/admin/roles/{$role->id}/details", ['name' => 'x'])->assertForbidden();
        $this->getJson('/admin/permission-options')->assertForbidden();
    }

    public function test_admin_can_view_and_update_a_role_with_its_permissions(): void
    {
        $role = Role::findOrCreate('editor', 'web');
        Permission::findOrCreate('edit-posts', 'web');
        Permission::findOrCreate('view-posts', 'web');
        $this->actingAs($this->makeUser('admin'));

        $this->getJson('/admin/permission-options')->assertOk()
            ->assertJsonFragment(['value' => 'edit-posts', 'label' => 'edit-posts']);

        $this->putJson("/admin/roles/{$role->id}/details", ['name' => 'content-editor', 'permissions' => ['edit-posts', 'view-posts']])
            ->assertOk()->assertJsonPath('data.permissions', ['edit-posts', 'view-posts']);

        $this->getJson("/admin/roles/{$role->id}/details")->assertOk()
            ->assertJsonPath('data.name', 'content-editor')
            ->assertJsonPath('data.permissions', ['edit-posts', 'view-posts']);

        $this->putJson("/admin/roles/{$role->id}/details", ['name' => 'content-editor', 'permissions' => ['no-such-permission']])
            ->assertStatus(422)->assertJsonValidationErrors('permissions.0');
    }

    public function test_admin_can_create_a_role_with_permissions(): void
    {
        Permission::findOrCreate('view-posts', 'web');
        $this->actingAs($this->makeUser('admin'));

        $this->postJson('/admin/roles/details', ['name' => 'reviewer', 'permissions' => ['view-posts']])
            ->assertCreated()
            ->assertJsonPath('data.name', 'reviewer')
            ->assertJsonPath('data.permissions', ['view-posts']);

        $this->postJson('/admin/roles/details', ['name' => 'reviewer'])->assertStatus(422)->assertJsonValidationErrors('name');

        $this->actingAs($this->makeUser('user'));
        $this->postJson('/admin/roles/details', ['name' => 'sneaky'])->assertForbidden();
    }

    public function test_users_and_roles_tables_get_a_create_button(): void
    {
        $this->seed(UserManagementSeeder::class);

        $schema = fn (string $slug) => json_decode(DB::table('table_schemas')->where('slug', $slug)->value('schema'), true);
        $this->assertSame(['form' => 'user-details', 'label' => 'Create user'], $schema('user-table')['create']);
        $this->assertSame(['form' => 'role-details', 'label' => 'Create role'], $schema('role-table')['create']);
        $this->assertArrayNotHasKey('create', $schema('permission-table'));

        // Existing installs: only the missing "create" key is added; other edits stay.
        $users = $schema('user-table');
        unset($users['create']);
        $users['default_view_mode'] = 'table';
        DB::table('table_schemas')->where('slug', 'user-table')->update(['schema' => json_encode($users)]);
        $this->seed(UserManagementSeeder::class);
        $this->assertSame('table', $schema('user-table')['default_view_mode']);
        $this->assertSame('user-details', $schema('user-table')['create']['form']);

        // The list page renders the button.
        $this->actingAs($this->makeUser('super-admin'));
        $uid = DB::table('table_schemas')->where('slug', 'user-table')->value('uid');
        $this->get("/list/{$uid}")->assertOk()
            ->assertSee('id="createRecordBtn"', false)
            ->assertSee('data-form="user-details"', false);

        $schemaForm = json_decode(DB::table('form_schemas')->where('slug', 'user-details')->value('schema'), true);
        $this->assertSame('/api/foundation/v1/business-entity/store/user-account', $schemaForm['modes']['create']['endpoint']);
        $this->assertSame(['create'], collect($schemaForm['fields'])->firstWhere('id', 'user__password')['requiredIn']);
    }

    public function test_the_users_table_uses_the_user_components(): void
    {
        $this->seed(UserManagementSeeder::class);
        $schema = fn () => json_decode(DB::table('table_schemas')->where('slug', 'user-table')->value('schema'), true);

        // Component keys, registered by this module (records/user/*): the
        // table's list rows, and the record through its form's view mode.
        $this->assertSame('user.summary', $schema()['row_component']);
        $this->assertArrayNotHasKey('details_component', $schema());
        $form = json_decode(DB::table('form_schemas')->where('slug', 'user-details')->value('schema'), true);
        $this->assertSame('user.details', $form['modes']['view']['component']);
        $registry = app(RecordComponents::class);
        $this->assertTrue($registry->has('user.summary'));
        $this->assertSame('details', $registry->all('details')['user.details']['kind']);

        // Existing installs: the old keys move (summary_component → row_component,
        // the seeded details_component is dropped, older view paths become keys).
        $users = $schema();
        unset($users['row_component']);
        $users['summary_component'] = 'userinterface::components.lab-user-summary';
        $users['details_component'] = 'userinterface::components.lab-user-details';
        DB::table('table_schemas')->where('slug', 'user-table')->update(['schema' => json_encode($users)]);
        $this->seed(UserManagementSeeder::class);
        $this->assertSame('user.summary', $schema()['row_component']);
        $this->assertArrayNotHasKey('summary_component', $schema());
        $this->assertArrayNotHasKey('details_component', $schema());

        // Components an admin chose are kept.
        $users = $schema();
        unset($users['row_component']);
        $users['summary_component'] = 'acme.my-own-row';
        $users['details_component'] = 'acme.my-own-details';
        DB::table('table_schemas')->where('slug', 'user-table')->update(['schema' => json_encode($users)]);
        $this->seed(UserManagementSeeder::class);
        $this->assertSame('acme.my-own-row', $schema()['row_component']);
        $this->assertSame('acme.my-own-details', $schema()['details_component']);

        // Served to the table by key; the details component shows roles as badges.
        $this->actingAs($this->makeUser('super-admin'));
        $html = $this->getJson('/api/user-interface/v1/components/templates/user.details')
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('response_schema.data.deprecated', false)
            ->json('response_schema.data.html');
        $this->assertStringContainsString('"labels.roles"', $html);
        $this->assertStringContainsString('"type": "badges"', $html);

        // Both components render, with bindings the browser can parse (picture path included).
        foreach (['user.summary', 'user.details'] as $key) {
            $html = $this->getJson("/api/user-interface/v1/components/templates/{$key}")
                ->assertOk()->json('response_schema.data.html');
            preg_match('#<script type="application/json" data-component-bindings>(.*?)</script>#s', $html, $match);
            $bindings = json_decode($match[1] ?? '', true);
            $this->assertIsArray($bindings, "{$key}: bindings are not valid JSON");
            $this->assertSame('/profile-picture/', $bindings['__LAB_AVATAR_BLOCK__']['fields']['src']['prefix']);
        }

        // Older schemas with a view path still work (deprecated); unknown keys are 404.
        $this->getJson('/api/user-interface/v1/components/templates/userinterface::components.lab-user-details')
            ->assertOk()->assertJsonPath('response_schema.data.deprecated', true);
        $this->getJson('/api/user-interface/v1/components/templates/nope.details')->assertNotFound();
    }

    public function test_admin_roles_cannot_be_renamed(): void
    {
        $this->actingAs($this->makeUser('super-admin'));
        $admin = Role::findOrCreate('admin', 'web');

        $this->putJson("/admin/roles/{$admin->id}/details", ['name' => 'boss'])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $this->assertSame('admin', $admin->fresh()->name);

        // Permissions of an admin role can still change.
        Permission::findOrCreate('view-posts', 'web');
        $this->putJson("/admin/roles/{$admin->id}/details", ['name' => 'admin', 'permissions' => ['view-posts']])->assertOk();
    }
}
