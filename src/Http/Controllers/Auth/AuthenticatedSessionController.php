<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Illuminate\Routing\Controller;
use Iquesters\UserManagement\Helpers\LoginHelper;
use Iquesters\UserManagement\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View|RedirectResponse
    {
        if ((ConfProvider::from(Module::USER_MGMT)->signin_flow ?? 'classic') === 'unified') {
            return redirect()->route('auth.unified');
        }

        return view('usermanagement::auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Use login helper
        LoginHelper::process_login(Auth::user());

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Revoke Sanctum tokens
        if (Auth::check()) {
            LoginHelper::process_logout(Auth::user());
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
