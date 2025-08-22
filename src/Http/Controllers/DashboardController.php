<?php

namespace Iquesters\UserManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
}