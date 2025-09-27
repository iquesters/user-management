<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use App\Http\Controllers\ContextController;
use Illuminate\Routing\Controller;
use App\Models\User;
use Iquesters\UserManagement\Models\UserMeta;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    /**
     * Redirect the user to Google's OAuth page.
     */
    public function google_redirect(Request $request)
    {
        $redirect_url = $request?->query('redirect_url');

        if ($redirect_url) {
            Session::put('redirect_url', $redirect_url);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the OAuth callback from Google.
     */
    public function google_callback()
    {
        if (app()->environment('local')) {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } else {
            $googleUser = Socialite::driver('google')->user();
        }

        $user = $this->handle_google_user($googleUser);

        Auth::login($user);

        return $this->redirect_after_login();
    }

    /**
     * Handle Google One Tap sign-in callback.
     */
    public function google_onetap_callback(Request $request)
    {
        Log::debug('Google One Tap callback started');

        $client = new GoogleClient([
            'client_id' => config('usermanagement.google.client_id'),
        ]);

        $credential = $request->input('credential');
        Log::debug('Google One Tap credential: ' . $credential);

        $payload = $client->verifyIdToken($credential);

        if ($payload) {
            $googleUser = (object)[
                'name'   => $payload['name'],
                'email'  => $payload['email'],
                'id'     => $payload['sub'],
                'avatar' => $payload['picture'],
            ];

            $user = $this->handle_google_user($googleUser);

            Auth::login($user);

            return $this->redirect_after_login($request->redirect_url);
        }

        return redirect()->back()->with('error', 'Invalid Google token');
    }

    /**
     * Handle creating/updating user from Google account.
     */
    protected function handle_google_user($googleUser)
    {
        Log::debug('Google user payload:', (array) $googleUser);

        $user = User::where('email', $googleUser->email)->first();

        if (!$user) {
            Log::debug('Creating new user for email: ' . $googleUser->email);

            $user = User::create([
                'uid'      => Str::ulid(),
                'name'     => $googleUser->name,
                'email'    => $googleUser->email,
                'status'   => 'active',
                'password' => bcrypt(Str::random(16)),
            ]);

            // Save Google ID
            UserMeta::create([
                'ref_parent' => $user->id,
                'meta_key'   => 'google_id',
                'meta_value' => $googleUser->id,
                'status'     => 'active',
            ]);

            // Save avatar/logo
            UserMeta::create([
                'ref_parent' => $user->id,
                'meta_key'   => 'logo',
                'meta_value' => $googleUser->avatar,
                'status'     => 'active',
            ]);

            $user->markEmailAsVerified();

            $user->assignRole(config('usermanagement.default_user_role', 'user'));
        } else {
            Log::debug('User exists: ' . $googleUser->email);

            if (empty($user->name)) {
                $user->name = $googleUser->name;
                $user->save();
            }

            // Update or create logo
            UserMeta::updateOrCreate(
                [
                    'ref_parent' => $user->id,
                    'meta_key'   => 'logo',
                ],
                [
                    'meta_value' => $googleUser->avatar,
                    'status'     => 'active',
                ]
            );

            // Update or create Google ID
            UserMeta::updateOrCreate(
                [
                    'ref_parent' => $user->id,
                    'meta_key'   => 'google_id',
                ],
                [
                    'meta_value' => $googleUser->id,
                    'status'     => 'active',
                ]
            );
        }

        return $user;
    }

    /**
     * Redirect the user after login (handles normal + One Tap).
     */
    protected function redirect_after_login($redirect_url = null)
    {
        $base_url = URL::to('/') . '/';

        if ($redirect_url && $redirect_url !== $base_url) {
            return redirect($redirect_url);
        }

        if (Session::get('redirect_url')) {
            return redirect(Session::get('redirect_url'));
        }

        return redirect()->route(config('usermanagement.default_auth_route'));
    }
}