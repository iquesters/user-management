<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Iquesters\Foundation\Models\Module;
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
        $this->assertSame(['name', 'email', 'phone', 'status', 'password', 'password_confirmation'], array_column($user['fields'], 'id'));
        // New password: edit mode only, and a blank value is not sent (keeps the current one).
        $password = collect($user['fields'])->firstWhere('id', 'password');
        $this->assertSame(['edit'], $password['showIn']);
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
