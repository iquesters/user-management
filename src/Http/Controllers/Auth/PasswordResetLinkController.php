<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Iquesters\UserManagement\Rules\RecaptchaRule;
use Iquesters\UserManagement\Support\AuthFormSchema;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\Foundation\Enums\Module;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('usermanagement::auth.forget-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $rules = AuthFormSchema::rules('password_reset_link-form') ?? [
            'email' => ['required', 'email'],
        ];
        $recaptcha = ConfProvider::from(Module::USER_MGMT)->recaptcha;
        $recaptchaEnabled = $recaptcha ? $recaptcha->enabled : false;

        if ($recaptchaEnabled) {
            $rules['recaptcha_token'] = ['required', new RecaptchaRule('password_reset_link', 0.5)];
        }

        $request->validate($rules);

        // Attempt to send the password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        $sent = $status == Password::RESET_LINK_SENT;

        // Schema-rendered forms submit via fetch() expecting JSON, not a
        // redirect (which fetch would follow silently without navigating or
        // surfacing the message). Classic form POSTs are unaffected.
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $sent,
                'message' => __($status),
                'errors' => $sent ? null : ['email' => [__($status)]],
            ], $sent ? 200 : 422);
        }

        return $sent
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}