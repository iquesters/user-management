<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        return $request->user()->getConnection()->transaction(function () use ($request) {
            $request->user()->newQuery()->whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
            $request->user()->refresh();
            // Users who signed up with Google or an OTP have no password yet: they set one.
            $hasPassword = $request->user()->hasUsablePassword();

            $validated = $request->validateWithBag('updatePassword', [
                'current_password' => $hasPassword ? ['required', 'current_password'] : ['nullable'],
                'password' => ['required', Password::defaults(), 'confirmed'],
            ]);

            $request->user()->update([
                'password' => Hash::make($validated['password']),
            ]);
            // The user now knows their password.
            $request->user()->meta()->where('meta_key', 'password_generated')->delete();

            $message = $hasPassword ? 'Password updated.' : 'Password set. You can now also sign in with it.';

            // Schema form ("user-password-update" / "user-password-set") submits with fetch().
            if ($request->wantsJson()) {
                session()->flash('success', $message);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect_url' => route('settings', ['tab' => 'password'], absolute: false),
                ]);
            }

            return back()->with('success', $message);
        });
    }
}
