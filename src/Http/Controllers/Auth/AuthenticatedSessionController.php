<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Illuminate\Routing\Controller;
use Iquesters\UserManagement\Http\Requests\Auth\LoginRequest;
use Iquesters\UserManagement\Models\UserMeta;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('usermanagement::auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Get the current login time before updating
        $currentLogin = UserMeta::where('ref_parent', $user->id)
            ->where('meta_key', 'current_login_at')
            ->first();

        // If there was a current login, move it to last login
        if ($currentLogin) {
            UserMeta::updateOrCreate(
                [
                    'ref_parent' => $user->id,
                    'meta_key' => 'last_login_at'
                ],
                [
                    'meta_value' => $currentLogin->meta_value,
                    'status' => 'active'
                ]
            );
        }

        // Update the current login time
        UserMeta::updateOrCreate(
            [
                'ref_parent' => $user->id,
                'meta_key' => 'current_login_at'
            ],
            [
                'meta_value' => Carbon::now()->toDateTimeString(),
                'status' => 'active'
            ]
        );

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}