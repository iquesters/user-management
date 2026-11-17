<?php

namespace Iquesters\UserManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Iquesters\Foundation\Support\UserModel;
use Iquesters\Foundation\System\Traits\Loggable;
use Iquesters\Organisation\Models\Organisation;

class DashboardController extends Controller
{
    use Loggable;

    public function index()
    {
        try {
            $user = UserModel::query()->find(Auth::id());

            $hasOrganisation = false;

            if ($user && method_exists($user, 'organisations')) {
                $hasOrganisation = $user->organisations()->count() > 0;
            }

            return view('usermanagement::dashboard.show', [
                'user' => $user,
                'hasOrganisation' => $hasOrganisation,
            ]);
        } catch (\Exception $e) {
            static::logError('DashboardController index error: '.$e->getMessage(), [
                'exception' => $e,
                'request' => request()->all(),
            ]);

            return back()->with('error', 'Failed to load dashboard. Please try again later.');
        }
    }

    public function createOrganisation(Request $request)
    {
        try {
            $user = UserModel::query()->find(Auth::id());

            // Organisations are provided by the optional iquesters/organisation package.
            if (! class_exists(Organisation::class) || ! method_exists($user, 'assignOrganisation')) {
                return back()->with('error', 'Organisations are not available in this application.');
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $organisation = Organisation::create([
                'uid' => Str::ulid(),
                'name' => $request->name,
                'description' => $request->description ?? 'Organisation created by '.$user->name,
                'status' => 'active',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Assign the organisation to the user
            $user->assignOrganisation($organisation);

            static::logInfo('Organisation created and assigned to user', [
                'user_id' => $user->id,
                'organisation_id' => $organisation->id,
                'organisation_uid' => $organisation->uid,
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Organisation created successfully!');

        } catch (\Exception $e) {
            static::logError('Failed to create organisation from dashboard', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to create organisation. Please try again.');
        }
    }

    public function profileImage()
    {
        $logoUrl = Auth::user()->getMeta('logo');

        if (! $logoUrl) {
            abort(404);
        }

        try {
            $response = Http::get($logoUrl);

            return response($response->body())
                ->header('Content-Type', $response->header('Content-Type'))
                ->header('Cache-Control', 'public, max-age=86400'); // Cache for 24 hours
        } catch (\Exception $e) {
            abort(404);
        }
    }

    /**
     * ✅ Super Admin Dashboard
     */
    public function adminDashboard()
    {
        $user = Auth::user();

        // Hardcoded roles allowed to access this dashboard
        $roles = ['super-admin'];

        // 🔒 If user's role is not in the allowed list, show 404
        if (! $user->hasAnyRole($roles)) {
            abort(404);
        }

        // ✅ Only pass the logged-in user to the view
        return view('usermanagement::dashboard.admin', [
            'user' => $user,
        ]);
    }
}
