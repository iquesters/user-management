<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Iquesters\UserManagement\Rules\RecaptchaRule;
use Iquesters\UserManagement\Support\AuthFormSchema;
use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\Foundation\Enums\Module;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('usermanagement::auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $rules = AuthFormSchema::rules('password_reset-form') ?? [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        $recaptcha = ConfProvider::from(Module::USER_MGMT)->recaptcha;
        $recaptchaEnabled = $recaptcha ? $recaptcha->enabled : false;

        if ($recaptchaEnabled) {
            $rules['recaptcha_token'] = ['required', new RecaptchaRule('password_reset', 0.5)];
        }

        $request->validate($rules);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        $reset = $status === Password::PASSWORD_RESET;

        // Schema-rendered forms submit via fetch() expecting JSON, not a
        // redirect (which fetch would follow silently without navigating or
        // surfacing the message). Classic form POSTs are unaffected.
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $reset,
                'message' => __($status),
                'errors' => $reset ? null : ['email' => [__($status)]],
                'redirect_url' => $reset ? route('login') : null,
            ], $reset ? 200 : 422);
        }

        return $reset
            ? redirect()->route('login')->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}