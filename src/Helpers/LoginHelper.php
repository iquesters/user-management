<?php

namespace Iquesters\UserManagement\Helpers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Iquesters\UserManagement\Models\UserMeta;

class LoginHelper extends BaseAuthHelper
{
    /**
     * Handle user login and update login ti mestamps with device info
     * 
     * @param User $user
     * @return bool
     */
    public static function process_login(User $user): bool
    {
        Log::debug('Processing login for user: ' . $user->email);

        try {
            // Login the user
            Auth::login($user);

            // Update login timestamps and device info
            self::update_login_info($user);

            Log::debug('User logged in successfully: ' . $user->email);

            return true;
        } catch (\Exception $e) {
            Log::error('Login process failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update login info: timestamps, IP, country, timezone, session token, etc
     * 
     * @param User $user
     * @return void
     */
    private static function update_login_info(User $user): void
    {
        // Get the current login time before updating
        $currentLogin = UserMeta::where('ref_parent', $user->id)
            ->where('meta_key', 'current_login_at')
            ->first();

        // If there was a current login, move it to last login
        if ($currentLogin) {
            UserMeta::updateOrCreate(
                [
                    'ref_parent' => $user->id,
                    'meta_key' => 'last_login_at',
                ],
                [
                    'meta_value' => $currentLogin->meta_value,
                    'status' => 'active',
                ]
            );
        }

        // Update the current login time
        UserMeta::updateOrCreate(
            [
                'ref_parent' => $user->id,
                'meta_key' => 'current_login_at',
            ],
            [
                'meta_value' => Carbon::now()->toDateTimeString(),
                'status' => 'active',
            ]
        );

        // Save IP Address
        self::save_user_meta($user->id, 'login_ip_address', self::get_client_ip());

        // Save User Agent (raw browser/device string)
        self::save_user_meta($user->id, 'login_user_agent', self::get_user_agent());

        // Save Session Token
        self::save_user_meta($user->id, 'session_token', session()->getId());

        // Save Country/Locale/Timezone
        self::save_user_meta($user->id, 'login_country', self::get_country());
        self::save_user_meta($user->id, 'login_locale', self::get_locale());
        self::save_user_meta($user->id, 'login_timezone', self::get_timezone());

        Log::debug('Login info updated for user: ' . $user->email);
    }
}