<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Iquesters\UserManagement\Helpers\LoginHelper;
use Iquesters\UserManagement\Helpers\RegistrationHelper;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Iquesters\UserManagement\Models\UserMeta;

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

        // ✅ Read from your custom config
        $googleConfig = collect(config('usermanagement.social_login.providers'))
            ->firstWhere('provider', 'google')['config'] ?? [];

        // ✅ Build Socialite provider manually
        $provider = Socialite::buildProvider(
            \Laravel\Socialite\Two\GoogleProvider::class,
            [
                'client_id'     => $googleConfig['client_id'] ?? null,
                'client_secret' => $googleConfig['client_secret'] ?? null,
                'redirect'      => $googleConfig['redirect'] ?? null,
            ]
        );

        return $provider->redirect();
    }

    /**
     * Handle the OAuth callback from Google.
     */
    public function google_callback()
    {
        // ✅ Read config again for callback
        $googleConfig = collect(config('usermanagement.social_login.providers'))
            ->firstWhere('provider', 'google')['config'] ?? [];

        $provider = Socialite::buildProvider(
            \Laravel\Socialite\Two\GoogleProvider::class,
            [
                'client_id'     => $googleConfig['client_id'] ?? null,
                'client_secret' => $googleConfig['client_secret'] ?? null,
                'redirect'      => $googleConfig['redirect'] ?? null,
            ]
        );

        $googleUser = app()->environment('local')
            ? $provider->stateless()->user()
            : $provider->user();

        $user = $this->sync_google_user($googleUser);

        LoginHelper::process_login($user);

        return $this->redirect_after_login();
    }

    /**
     * Handle Google One Tap sign-in callback.
     */
    public function google_onetap_callback(Request $request)
    {
        Log::debug('Google One Tap callback started');

        $googleConfig = collect(config('usermanagement.social_login.providers'))
            ->firstWhere('provider', 'google')['config'] ?? [];

        $client = new GoogleClient([
            'client_id' => $googleConfig['client_id'],
        ]);

        $credential = $request->input('credential');
        Log::debug('Google One Tap credential: ' . $credential);

        $payload = $client->verifyIdToken($credential);

        if (!$payload) {
            return redirect()->back()->with('error', 'Invalid Google token');
        }

        $googleUser = (object)[
            'name'   => $payload['name'],
            'email'  => $payload['email'],
            'id'     => $payload['sub'],
            'avatar' => $payload['picture'],
        ];

        $user = $this->sync_google_user($googleUser);

        LoginHelper::process_login($user);

        return $this->redirect_after_login($request->redirect_url);
    }

    /**
     * Sync/Create user from Google data or return existing user.
     */
    protected function sync_google_user($googleUser): User
    {
        Log::debug('Google user payload:', (array) $googleUser);

        $user = User::where('email', $googleUser->email)->first();

        if (!$user) {
            $user = RegistrationHelper::register_user(
                name: $googleUser->name,
                email: $googleUser->email,
                password: null,
                email_verified: true,
                meta: [
                    'google_id' => $googleUser->id,
                    'logo'      => $googleUser->avatar,
                ]
            );
        } else {
            Log::debug('User already exists: ' . $googleUser->email);

            if (empty($user->name)) {
                $user->update(['name' => $googleUser->name]);
            }

            UserMeta::updateOrCreate(
                ['ref_parent' => $user->id, 'meta_key' => 'google_id'],
                ['meta_value' => $googleUser->id, 'status' => 'active']
            );

            UserMeta::updateOrCreate(
                ['ref_parent' => $user->id, 'meta_key' => 'logo'],
                ['meta_value' => $googleUser->avatar, 'status' => 'active']
            );
        }

        return $user;
    }

    /**
     * Redirect after login.
     */
    protected function redirect_after_login($redirect_url = null)
    {
        $base_url = URL::to('/') . '/';

        if ($redirect_url && $redirect_url !== $base_url) {
            return redirect($redirect_url);
        }

        if (Session::has('redirect_url')) {
            $url = Session::pull('redirect_url');
            return redirect($url);
        }

        return redirect()->route(config('usermanagement.default_auth_route'));
    }
}