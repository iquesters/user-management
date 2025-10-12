<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Iquesters\UserManagement\Helpers\RegistrationHelper;
use Iquesters\UserManagement\Helpers\LoginHelper;
use Iquesters\UserManagement\Rules\RecaptchaRule;
use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('usermanagement::auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        if (config('usermanagement.recaptcha.enabled')) {
            $rules['recaptcha_token'] = ['required', new RecaptchaRule('register', 0.5)];
        }

        $validated = $request->validate($rules);

        // Use registration helper
        $user = RegistrationHelper::register_user(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            email_verified: false
        );

        // Use login helper
        LoginHelper::process_login($user);

        return redirect(route('dashboard', absolute: false));
    }
}