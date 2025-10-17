<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Iquesters\UserManagement\Helpers\LoginHelper;
use Iquesters\UserManagement\Helpers\RegistrationHelper;
use Iquesters\Foundation\Support\ConfigProvider;
use Iquesters\Foundation\Enums\Module;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Iquesters\UserManagement\Config\UserManagementKeys;
use Laravel\Socialite\Facades\Socialite;
use Iquesters\UserManagement\Models\UserMeta;

class GoogleController extends Controller
{
    /**
     * Redirect to Google's OAuth page.
     */
    public function google_redirect(Request $request)
    {
        $redirect_url = $request->query('redirect_url');
        if ($redirect_url) {
            Session::put('redirect_url', $redirect_url);
        }

        $googleProvider = $this->getGoogleProvider();
        if (!$googleProvider || !$googleProvider->isEnabled()) {
            Log::error('Google login disabled or not configured');
            return redirect()->route('login')->with('error', 'Google login is not configured.');
        }

        $provider = Socialite::buildProvider(
            \Laravel\Socialite\Two\GoogleProvider::class,
            [
                'client_id'     => $googleProvider->client_id,
                'client_secret' => $googleProvider->client_secret,
                'redirect'      => $googleProvider->redirect,
            ]
        );

        return $provider->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function google_callback()
    {
        $googleProvider = $this->getGoogleProvider();
        if (!$googleProvider || !$googleProvider->isEnabled()) {
            return redirect()->route('login')->with('error', 'Google login is not configured.');
        }

        $provider = Socialite::buildProvider(
            \Laravel\Socialite\Two\GoogleProvider::class,
            [
                'client_id'     => $googleProvider->client_id,
                'client_secret' => $googleProvider->client_secret,
                'redirect'      => $googleProvider->redirect,
            ]
        );

        try {
            $googleUser = app()->environment('local')
                ? $provider->stateless()->user()
                : $provider->user();

            $user = $this->sync_google_user($googleUser);
            LoginHelper::process_login($user);

            return $this->redirect_after_login();
        } catch (\Exception $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Google login failed.');
        }
    }

    /**
     * Google One Tap callback.
     */
    public function google_onetap_callback(Request $request)
    {
        $googleProvider = $this->getGoogleProvider();
        if (!$googleProvider || !$googleProvider->isEnabled()) {
            return redirect()->back()->with('error', 'Google login not configured.');
        }

        $client = new GoogleClient(['client_id' => $googleProvider->client_id]);
        $payload = $client->verifyIdToken($request->input('credential'));

        if (!$payload) {
            return redirect()->back()->with('error', 'Invalid Google token.');
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
     * Fetch Google provider using SocialLoginConfig object.
     */
    protected function getGoogleProvider()
    {
        $config = ConfigProvider::from(Module::USER_MGMT);
        $socialLogin = $config->get('social_logins');

        if (!$socialLogin || !$socialLogin->isEnabled()) {
            return null;
        }

        return $socialLogin->providers['google'] ?? null;
    }

    /**
     * Sync or create user from Google payload.
     */
    protected function sync_google_user($googleUser): User
    {
        $user = User::firstOrNew(['email' => $googleUser->email]);

        if (!$user->exists) {
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
        $defaultRoute = ConfigProvider::from(Module::USER_MGMT)->get(UserManagementKeys::DEFAULT_AUTH_ROUTE);
        
        $base_url = URL::to('/') . '/';

        if ($redirect_url && $redirect_url !== $base_url) {
            return redirect($redirect_url);
        }

        if (Session::has('redirect_url')) {
            return redirect(Session::pull('redirect_url'));
        }

        return redirect()->route($defaultRoute);
    }
}