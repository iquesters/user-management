<?php

namespace Iquesters\UserManagement\Http\Controllers;

use App\Logging\Logger;
use App\Models\Organisation;
use App\Models\OrganisationUsers;
use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            Logger::info('Fetching all users');
            $users = User::with('roles')->get();
            $roles = Role::all();
            Logger::debug('Users fetched successfully', ['count' => $users->count()]);
            return view('users.index', compact('users', 'roles'));
        } catch (Exception $e) {
            Logger::error('Error fetching users', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'An error occurred while fetching users.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            Logger::info('Displaying the user creation form');
            $roles = Role::all();
            return view('users.create', compact('roles'));
        } catch (Exception $e) {
            Logger::error('Error displaying user creation form', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'An error occurred while displaying the form.');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            Logger::info('Starting user creation process');
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'roles' => ['required', 'array'],
                // 'organisation_uid' => ['nullable', 'string'], // Add this line
            ]);

            Logger::debug('Validation passed', ['name' => $validated['name'], 'email' => $validated['email']]);

            $user = User::create([
                'uid' => Str::ulid(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            Logger::info('User created successfully', ['id' => $user->id]);

            $user->syncRoles($request->roles);
            Logger::debug('Roles synced for user', ['id' => $user->id, 'roles' => $request->roles]);
            // Logger::debug('organisation_uid', ['organisation_uid' => $request->organisation_uid]);
            // If organisation_uid is provided, add the user to the organisation
            // @todo: get orgUid from route
            // if ($request->has('organisation_uid')) {
            //     $organisation = Organisation::where('uid', $request->organisation_uid)->first();
                //@todo: check if creator user and organisation are in the same organisation

                // if ($organisation) {
                //     OrganisationUsers::create([
                //         'ref_organisation' => $organisation->id,
                //         'ref_user' => $user->id,
                //         'status' => 'active',
                //         'created_by' => Auth::id(),
                //         'updated_by' => Auth::id(),
                //     ]);


                //     return redirect()->route('organisations.users.index', $organisation->uid)
                //         ->with('success', 'User created and added to organisation successfully.');
                // }
            // }

            // Default redirect if no organisation context
            return redirect()->route('users.index') // Make sure you have this route defined
                ->with('success', 'User created successfully.');
        } catch (Exception $e) {
            Logger::error('Error creating user', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while creating the user.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        try {
            Logger::info('Displaying the user edit form', ['id' => $user->id]);
            $roles = Role::all();
            $userRoles = $user->roles->pluck('id')->toArray();
            return view('users.edit', compact('user', 'roles', 'userRoles'));
        } catch (Exception $e) {
            Logger::error('Error displaying user edit form', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'An error occurred while displaying the edit form.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        try {
            Logger::info('Starting user update process', ['id' => $user->id]);
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id),
                ],
                'password' => $request->filled('password') ? ['string', 'min:8', 'confirmed'] : [],
                'roles' => ['required', 'array'],
            ]);

            Logger::debug('Validation passed', ['name' => $validated['name'], 'email' => $validated['email']]);

            $user->name = $validated['name'];
            $user->email = $validated['email'];

            if ($request->filled('password')) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
            Logger::info('User updated successfully', ['id' => $user->id]);

            $user->syncRoles($request->roles);
            Logger::debug('Roles synced for user', ['id' => $user->id, 'roles' => $request->roles]);

            return redirect()->route('users.index')
                ->with('success', 'User updated successfully.');
        } catch (Exception $e) {
            Logger::error('Error updating user', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'An error occurred while updating the user.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            Logger::info('Starting user deletion process', ['id' => $user->id]);

            // Prevent deleting yourself
            if ($user->id === Auth::user()->id) {
                Logger::warning('User attempted to delete themselves', ['id' => $user->id]);
                return redirect()->route('users.index')
                    ->with('error', 'You cannot delete yourself.');
            }

            $user->update(['status' => 'deleted']);
            Logger::info('User deleted successfully', ['id' => $user->id]);

            return redirect()->route('users.index')
                ->with('success', 'User deleted successfully.');
        } catch (Exception $e) {
            Logger::error('Error deleting user', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'An error occurred while deleting the user.');
        }
    }
}