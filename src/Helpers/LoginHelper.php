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
     * Handle user login and update login timestamps with device info
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

            // Ensure Sanctum token exists for API access
            $plainTextToken = self::ensure_sanctum_token($user);

            // Store the plain text token in user meta for later retrieval
            self::save_user_meta($user->id, 'sanctum_plain_token', $plainTextToken);

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
     * Ensure user has a valid Sanctum token for API access
     * 
     * @param User $user
     * @return string Returns the PLAIN TEXT token
     */
    private static function ensure_sanctum_token(User $user): string
    {
        Log::debug('Ensuring Sanctum token for user: ' . $user->email);
        
        // Check if we already have a stored plain text token
        $storedToken = UserMeta::where('ref_parent', $user->id)
            ->where('meta_key', 'sanctum_plain_token')
            ->where('status', 'active')
            ->first();
            
        if ($storedToken && !empty($storedToken->meta_value)) {
            Log::debug('Found stored plain text token for user: ' . $user->email);
            
            // Verify the token still exists in database
            $tokenExists = $user->tokens()
                ->where('name', 'web-app')
                ->exists();
                
            if ($tokenExists) {
                return $storedToken->meta_value;
            } else {
                Log::debug('Stored token no longer valid, creating new one');
                // Token was revoked, delete the stored plain text token
                $storedToken->delete();
            }
        }

        // Create new token
        $tokenResult = $user->createToken('web-app', ['*']);
        $plainTextToken = $tokenResult->plainTextToken;
        
        Log::debug('New Sanctum token created for user: ' . $user->email);
        Log::debug('New Plain Text Token (first 20 chars): ' . substr($plainTextToken, 0, 20) . '...');
        
        // Store token reference in user meta
        self::save_user_meta($user->id, 'sanctum_token_created_at', Carbon::now()->toDateTimeString());
        
        return $plainTextToken;
    }

    /**
     * Get the current Sanctum token for the user
     * 
     * @param User $user
     * @return string|null Returns the PLAIN TEXT token
     */
    public static function get_sanctum_token(User $user): ?string
    {
        // Get the stored plain text token from user meta
        $storedToken = UserMeta::where('ref_parent', $user->id)
            ->where('meta_key', 'sanctum_plain_token')
            ->where('status', 'active')
            ->first();
            
        if (!$storedToken || empty($storedToken->meta_value)) {
            Log::debug('No stored plain text token found for user: ' . $user->email);
            return null;
        }

        // Verify the token still exists in database
        $tokenExists = $user->tokens()
            ->where('name', 'web-app')
            ->exists();
            
        if (!$tokenExists) {
            Log::warning('Stored plain text token exists but database token was revoked');
            // Clean up the stored token
            $storedToken->delete();
            return null;
        }

        Log::debug('Retrieved plain text token for user: ' . $user->email);
        return $storedToken->meta_value;
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

    /**
     * Logout user and revoke Sanctum tokens
     * 
     * @param User $user
     * @return bool
     */
    public static function process_logout(User $user): bool
    {
        try {
            // Revoke ALL Sanctum tokens for this user
            $user->tokens()->delete();
            
            // Delete stored plain text tokens
            UserMeta::where('ref_parent', $user->id)
                ->where('meta_key', 'sanctum_plain_token')
                ->delete();
            
            Log::debug('All Sanctum tokens revoked for user: ' . $user->email);
            return true;
        } catch (\Exception $e) {
            Log::error('Logout process failed: ' . $e->getMessage());
            return false;
        }
    }
}