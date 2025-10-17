<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Iquesters\UserManagement\Rules\RecaptchaRule;
use Illuminate\Routing\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Iquesters\Foundation\Support\ConfigProvider;
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
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'email' => ['required', 'email'],
        ];
        $recaptcha = ConfigProvider::from(Module::USER_MGMT)->get('recaptcha');
        $recaptchaEnabled = $recaptcha ? $recaptcha->isEnabled() : false;
        
        if ($recaptchaEnabled) {
            $rules['recaptcha_token'] = ['required', new RecaptchaRule('password_reset_link', 0.5)];
        }

        $request->validate($rules);

        // Attempt to send the password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}