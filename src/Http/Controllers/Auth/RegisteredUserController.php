<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Iquesters\UserManagement\Helpers\RegistrationHelper;
use Iquesters\UserManagement\Helpers\LoginHelper;
use Iquesters\UserManagement\Rules\RecaptchaRule;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\Foundation\Enums\Module;
use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

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
    public function store_old(Request $request): RedirectResponse
    {
        Log::debug('Registration request received');
        $recaptcha = ConfProvider::from(Module::USER_MGMT)->recaptcha;
        $recaptchaEnabled = $recaptcha ? $recaptcha->enabled : false;

        Log::debug('Registration request received', [
            'recaptcha_enabled' => $recaptchaEnabled,
            'recaptcha_config' => $recaptcha
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



    public function store(Request $request): JsonResponse|RedirectResponse
    {
        try {
            Log::info("Registration API hit");

            // ------------------------------------------------
            //Fetch config values
            // ------------------------------------------------
            $signinIdentifier = ConfProvider::from(Module::USER_MGMT)->signin_identifier;
            $recaptchaConf    = ConfProvider::from(Module::USER_MGMT)->recaptcha;
            $recaptchaEnabled = $recaptchaConf ? $recaptchaConf->enabled : false;

            $identifierValue = $request->input($signinIdentifier);

            // ------------------------------------------------
            //Base validation rules (common)
            // ------------------------------------------------
            $rules = [];

            if ($signinIdentifier === 'email') {
                $rules = [
                    'name'     => ['required', 'string', 'max:255'],
                    'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],
                ];
            }

            if ($signinIdentifier === 'phone') {
                $rules = [
                    'phone' => ['required', 'digits:10', 'unique:users,phone'],
                    // optional name if you want
                    'name'  => ['nullable', 'string', 'max:255'],
                ];
            }

            // ------------------------------------------------
            // Add reCAPTCHA conditionally
            // ------------------------------------------------
            if ($recaptchaEnabled) {
                $rules['recaptcha_token'] = ['required', new RecaptchaRule('register', 0.5)];
            }

            // ------------------------------------------------
            // Validate
            // ------------------------------------------------
            $validated = $request->validate($rules);

            // ------------------------------------------------
            // Registration
            // ------------------------------------------------
            $user = RegistrationHelper::register_user(
                name: $validated['name'] ?? '',
                identifierType: $signinIdentifier,
                identifierValue: $identifierValue,
                password: $request->password,
                email_verified: false
            );

            // ------------------------------------------------
            // Login automatically (only for web form)
            // ------------------------------------------------
            if ($request->wantsJson() === false) {
                LoginHelper::process_login($user);

                return redirect(route('dashboard', absolute: false));
            }

            // ------------------------------------------------
            // 7️⃣ Return API JSON Response
            // ------------------------------------------------
            return response()->json([
                'status'  => true,
                'message' => 'User registered successfully',
                'data'    => $validated,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Throwable $e) {

            Log::error('Registration Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


}