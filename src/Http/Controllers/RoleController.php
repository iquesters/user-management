<?php

namespace Iquesters\UserManagement\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('usermanagement::roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            Log::info('Role creation request received', [
                'user_id' => auth()?->id(),
                'ip' => request()->ip(),
            ]);
            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
                'permissions' => ['nullable', 'array'],
            ]);

            Log::info('Creating new role', [
                'name' => $request->name,
                'permissions' => $request->permissions ?? [],
                'user_id' => auth()?->id(),
                'ip' => request()->ip(),
            ]);

            $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            return redirect()->route('roles.index')
                ->with('success', 'Role created successfully');
        } catch (\Exception $e) {
            Log::error('Error creating role', [
                'error' => $e->getMessage(),
                'user_id' => auth()?->id(),
                'ip' => request()->ip(),
            ]);
            return redirect()->back()->with('error', 'Failed to create role');
        }
    }

    public function show(Role $role)
    {
        $permissions = $role->permissions;

        return view('usermanagement::roles.show', compact('role', 'permissions'));
    }

    public function permissions(Role $role)
    {
        $permissions = Permission::all();
        $rolePermissions = $role->permissions()->get();

        return view('usermanagement::roles.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('usermanagement::roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        try {
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('roles', 'name')->ignore($role->id),
                ],
                'permissions' => ['required', 'array'],
            ]);

            $role->update(['name' => $request->name]);
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            return redirect()->route('roles.index')
                ->with('success', 'Role updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating role', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'user_id' => auth()?->id(),
                'ip' => request()->ip(),
            ]);
            return redirect()->back()->with('error', 'Failed to update role');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        try {
            // Prevent deletion of super-admin role
            if ($role->name === 'super-admin') {
                return redirect()->route('roles.index')
                    ->with('error', 'Cannot delete super-admin role');
            }

            $role->delete();

            return redirect()->route('roles.index')
                ->with('success', 'Role deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting role', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'user_id' => auth()?->id(),
                'ip' => request()->ip(),
            ]);
            return redirect()->back()->with('error', 'Failed to delete role');
        }
    }
}