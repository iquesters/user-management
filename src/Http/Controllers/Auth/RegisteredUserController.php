<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Helpers\RegistrationHelper;
use Iquesters\UserManagement\Helpers\LoginHelper;
use Iquesters\UserManagement\Rules\RecaptchaRule;
use Iquesters\UserManagement\Support\AuthFormSchema;
use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Iquesters\Foundation\System\Traits\Loggable;

class RegisteredUserController extends Controller
{
    use Loggable;

    /**
     * Display the registration view.
     */
    public function create(): View|RedirectResponse
    {
        if ((ConfProvider::from(Module::USER_MGMT)->signin_flow ?? 'classic') === 'unified') {
            return redirect()->route('auth.unified');
        }

        return view('usermanagement::auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store_old(Request $request): RedirectResponse
    {
        $recaptcha = ConfProvider::from(Module::USER_MGMT)->recaptcha;
        $recaptchaEnabled = $recaptcha ? $recaptcha->enabled : false;

        $this->logDebug("Registration request received | recaptcha_enabled={$recaptchaEnabled}");

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        if ($recaptchaEnabled) {
            $this->logDebug('reCAPTCHA validation enabled for registration');
            $rules['recaptcha_token'] = ['required', new RecaptchaRule('register', 0.5)];
        } else {
            $this->logDebug('reCAPTCHA validation disabled for registration');
        }

        $validated = $request->validate($rules);

        $this->logDebug("Registration validation passed | email={$validated['email']} | has_recaptcha_token=" . (isset($validated['recaptcha_token']) ? 'true' : 'false'));

        // Use registration helper
        $user = RegistrationHelper::register_user(
            name: $validated['name'],
            identifierType: 'email',
            identifierValue: $validated['email'],
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
            $this->logInfo('Registration API hit');

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
                $rules = AuthFormSchema::rules('register') ?? [
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
            // Log the new user in either way; only the response shape differs.
            // ------------------------------------------------
            LoginHelper::process_login($user);

            if ($request->wantsJson() === false) {
                return redirect(route('dashboard', absolute: false));
            }

            // ------------------------------------------------
            // Schema-rendered forms submit via fetch() expecting JSON with a
            // 'success' key (see AuthenticatedSessionController::store()) and
            // navigate themselves using redirect_url.
            // ------------------------------------------------
            return response()->json([
                'success' => true,
                'status'  => true,
                'message' => 'User registered successfully',
                'data'    => $validated,
                'redirect_url' => route('dashboard', absolute: false),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Throwable $e) {

            $this->logError('Registration Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


}
