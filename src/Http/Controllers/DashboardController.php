<?php

namespace Iquesters\UserManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Iquesters\Organisation\Models\Organisation;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = User::find(Auth::id());

            $hasOrganisation = false;

            if ($user && method_exists($user, 'organisations')) {
                $hasOrganisation = $user->organisations()->count() > 0;
            }

            return view('usermanagement::dashboard.show', [
                'user' => $user,
                'hasOrganisation' => $hasOrganisation
            ]);
        } catch (\Exception $e) {
            Log::error('DashboardController index error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => request()->all()
            ]);

            return back()->with('error', 'Failed to load dashboard. Please try again later.');
        }
    }
    
    public function createOrganisation(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $organisation = Organisation::create([
                'uid' => Str::ulid(),
                'name' => $request->name,
                'description' => $request->description ?? 'Organisation created by ' . $user->name,
                'status' => 'active',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Assign the organisation to the user
            $user->assignOrganisation($organisation);

            Log::info('Organisation created and assigned to user', [
                'user_id' => $user->id,
                'organisation_id' => $organisation->id,
                'organisation_uid' => $organisation->uid
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Organisation created successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to create organisation from dashboard', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to create organisation. Please try again.');
        }
    }
    
    public function profileImage()
    {
        $logoUrl = Auth::user()->getMeta('logo');
        
        if (!$logoUrl) {
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
}