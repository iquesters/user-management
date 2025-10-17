<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Iquesters\UserManagement\Helpers\RegistrationHelper;
use Iquesters\UserManagement\Helpers\LoginHelper;
use Iquesters\UserManagement\Rules\RecaptchaRule;
use Iquesters\Foundation\Support\ConfigProvider;
use Iquesters\Foundation\Enums\Module;
use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
        $config = ConfigProvider::from(Module::USER_MGMT);
        $recaptchaConfig = $config->get('recaptcha');
        $recaptchaEnabled = is_array($recaptchaConfig) ? ($recaptchaConfig['enabled'] ?? false) : false;

        Log::debug('Registration request received', [
            'recaptcha_enabled' => $recaptchaEnabled,
            'recaptcha_config' => $recaptchaConfig
        ]);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        if ($recaptchaEnabled) {
            Log::debug('reCAPTCHA validation enabled for registration');
            $rules['recaptcha_token'] = ['required', new RecaptchaRule('register', 0.5)];
        } else {
            Log::debug('reCAPTCHA validation disabled for registration');
        }

        $validated = $request->validate($rules);

        Log::debug('Registration validation passed', [
            'email' => $validated['email'],
            'has_recaptcha_token' => isset($validated['recaptcha_token'])
        ]);

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