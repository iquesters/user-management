<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use App\Constants\EntityStatus;
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
        // dd(config('usermanagement.google'));
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
        // $googleUser = Socialite::driver('google')->user();
        if (app()->environment('local')) {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } else {
            $googleUser = Socialite::driver('google')->user();
        }

        $this->handle_google_user($googleUser);

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

            $this->handle_google_user($googleUser);

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
                'uid'     => Str::ulid(),
                'name'    => $googleUser->name,
                'email'   => $googleUser->email,
                'status'  => 'active',
                'password'=> encrypt(Str::random(8)), // random placeholder
            ]);

            UserMeta::create([
                'ref_user' => $user->id,
                'key'      => 'google_id',
                'value'    => $googleUser->id,
                'status'   => 'active',
            ]);

            UserMeta::create([
                'ref_user' => $user->id,
                'key'      => 'logo',
                'value'    => $googleUser->avatar,
                'status'   => 'active',
            ]);

            $user->markEmailAsVerified();

            $user->assignRole(config('usermanagement.default_user_role', 'organizer'));
        } else {
            Log::debug('User exists: ' . $googleUser->email);

            if (empty($user->name)) {
                $user->name = $googleUser->name;
                $user->save();
            }

            if (!$user->getMeta('logo')) {
                UserMeta::create([
                    'ref_parent' => $user->id,
                    'meta_key'   => 'logo',
                    'meta_value' => $googleUser->avatar,
                    'status'     => 'active',
                ]);
            }

            if (!$user->getMeta('google_id')) {
                UserMeta::create([
                    'ref_parent' => $user->id,
                    'meta_key'   => 'google_id',
                    'meta_value' => $googleUser->id,
                    'status'     => 'active',
                ]);
            }
        }

        Auth::login($user);
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