<?php

namespace Iquesters\UserManagement\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Iquesters\Foundation\Http\Middleware\EnsureAdmin;
use Iquesters\Foundation\System\Traits\Loggable;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * JSON endpoints behind the "role-details" form schema (view + edit):
 * a role with its permissions, the permission options, and saving both.
 * Admin-only (routes/web/admin.php).
 */
class RoleFormController extends Controller
{
    use Loggable;

    public function show(Role $role): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->payload($role)]);
    }

    /** Options for the permissions checkbox list: [{value, label}]. */
    public function permissionOptions(): JsonResponse
    {
        $options = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (string $name) => ['value' => $name, 'label' => $name])
            ->values();

        return response()->json(['success' => true, 'data' => $options]);
    }

    /** Create a role with its permissions (role-details form, create mode). */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        static::logInfo('Role created', ['role_id' => $role->id]);

        return response()->json([
            'success' => true,
            'message' => 'Role created.',
            'data' => $this->payload($role),
        ], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', $role->guard_name)->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', $role->guard_name)],
        ]);

        // Access checks find admin roles by name: renaming one would lock admins out.
        if ($validated['name'] !== $role->name && in_array($role->name, EnsureAdmin::roles(), true)) {
            return response()->json([
                'success' => false,
                'message' => "The {$role->name} role is used for admin access and cannot be renamed.",
                'errors' => ['name' => ["The {$role->name} role cannot be renamed."]],
            ], 422);
        }

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        static::logInfo('Role updated', ['role_id' => $role->id, 'permissions' => count($validated['permissions'] ?? [])]);

        return response()->json([
            'success' => true,
            'message' => 'Role updated.',
            'data' => $this->payload($role->fresh()),
        ]);
    }

    private function payload(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions()->orderBy('name')->pluck('name')->values()->all(),
        ];
    }
}
