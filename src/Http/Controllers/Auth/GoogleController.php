<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Iquesters\UserManagement\Helpers\LoginHelper;
use Iquesters\UserManagement\Helpers\RegistrationHelper;
use Iquesters\Foundation\Support\ConfigProvider;
use Iquesters\Foundation\Enums\Module;
use Iquesters\UserManagement\Config\UserManagementKeys;
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

        // ✅ Use your custom config system with UserManagementKeys
        $config = ConfigProvider::from(Module::USER_MGMT);
        $googleConfig = $this->getGoogleConfig($config);

        if (empty($googleConfig['client_id']) || empty($googleConfig['client_secret'])) {
            Log::error('Google OAuth configuration missing');
            return redirect()->route('login')->with('error', 'Google login is not configured properly.');
        }

        // ✅ Build Socialite provider manually
        $provider = Socialite::buildProvider(
            \Laravel\Socialite\Two\GoogleProvider::class,
            [
                'client_id'     => $googleConfig['client_id'],
                'client_secret' => $googleConfig['client_secret'],
                'redirect'      => $googleConfig['redirect'],
            ]
        );

        return $provider->redirect();
    }

    /**
     * Handle the OAuth callback from Google.
     */
    public function google_callback()
    {
        // ✅ Use your custom config system with UserManagementKeys
        $config = ConfigProvider::from(Module::USER_MGMT);
        $googleConfig = $this->getGoogleConfig($config);

        if (empty($googleConfig['client_id']) || empty($googleConfig['client_secret'])) {
            Log::error('Google OAuth configuration missing in callback');
            return redirect()->route('login')->with('error', 'Google login is not configured properly.');
        }

        $provider = Socialite::buildProvider(
            \Laravel\Socialite\Two\GoogleProvider::class,
            [
                'client_id'     => $googleConfig['client_id'],
                'client_secret' => $googleConfig['client_secret'],
                'redirect'      => $googleConfig['redirect'],
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
            return redirect()->route('login')->with('error', 'Google login failed. Please try again.');
        }
    }

    /**
     * Handle Google One Tap sign-in callback.
     */
    public function google_onetap_callback(Request $request)
    {
        Log::debug('Google One Tap callback started');

        // ✅ Use your custom config system with UserManagementKeys
        $config = ConfigProvider::from(Module::USER_MGMT);
        $googleConfig = $this->getGoogleConfig($config);

        if (empty($googleConfig['client_id'])) {
            Log::error('Google client_id missing for One Tap');
            return redirect()->back()->with('error', 'Google login is not configured properly.');
        }

        $client = new GoogleClient([
            'client_id' => $googleConfig['client_id'],
        ]);

        $credential = $request->input('credential');
        Log::debug('Google One Tap credential received');

        $payload = $client->verifyIdToken($credential);

        if (!$payload) {
            Log::error('Invalid Google token in One Tap');
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
     * Get Google configuration from your custom config system using UserManagementKeys
     */
    protected function getGoogleConfig($config): array
    {
        // Method 1: Direct environment variable access using keys
        $googleConfig = [
            'client_id' => $config->get(UserManagementKeys::GOOGLE_CLIENT_ID),
            'client_secret' => $config->get(UserManagementKeys::GOOGLE_CLIENT_SECRET),
            'redirect' => $config->get(UserManagementKeys::GOOGLE_REDIRECT),
        ];

        // If direct method doesn't work, fall back to provider structure
        if (empty($googleConfig['client_id']) || empty($googleConfig['client_secret'])) {
            Log::debug('Falling back to provider structure for Google config');
            
            // Get the entire social_login config
            $socialLoginRaw = $config->get('social_login');
            
            // Get providers
            $providers = is_array($socialLoginRaw) ? $socialLoginRaw['providers'] ?? [] : [];
            
            // Find Google provider
            foreach ($providers as $provider) {
                if ($provider['provider'] === 'google') {
                    $googleConfig = $provider['config'] ?? [];
                    
                    // Apply environment overrides using keys
                    $googleConfig['client_id'] = $config->get(UserManagementKeys::GOOGLE_CLIENT_ID) ?? $googleConfig['client_id'];
                    $googleConfig['client_secret'] = $config->get(UserManagementKeys::GOOGLE_CLIENT_SECRET) ?? $googleConfig['client_secret'];
                    $googleConfig['redirect'] = $config->get(UserManagementKeys::GOOGLE_REDIRECT) ?? $googleConfig['redirect'];
                    
                    break;
                }
            }
        }

        return $googleConfig;
    }

    /**
     * Check if Google login is enabled
     */
    protected function isGoogleLoginEnabled($config): bool
    {
        // Check if Google login is specifically enabled
        $googleEnabled = $config->get(UserManagementKeys::GOOGLE_LOGIN);
        if (!is_null($googleEnabled)) {
            return filter_var($googleEnabled, FILTER_VALIDATE_BOOLEAN);
        }

        // Check if social login is globally enabled
        $socialEnabled = $config->get(UserManagementKeys::SOCIAL_LOGIN_ENABLED);
        if (!is_null($socialEnabled)) {
            return filter_var($socialEnabled, FILTER_VALIDATE_BOOLEAN);
        }

        return false;
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
        $defaultRoute = ConfigProvider::from(Module::USER_MGMT)->get(UserManagementKeys::DEFAULT_AUTH_ROUTE);
        
        $base_url = URL::to('/') . '/';

        if ($redirect_url && $redirect_url !== $base_url) {
            return redirect($redirect_url);
        }

        if (Session::has('redirect_url')) {
            $url = Session::pull('redirect_url');
            return redirect($url);
        }

        return redirect()->route($defaultRoute);
    }
}