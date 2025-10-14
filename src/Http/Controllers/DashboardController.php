<?php

namespace Iquesters\UserManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = User::find(Auth::id());
            return view('usermanagement::dashboard.show', [
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('DashboardController index error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => request()->all()
            ]);

            return back()->with('error', 'Failed to load dashboard. Please try again later.');
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