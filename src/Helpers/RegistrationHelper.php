<?php

namespace Iquesters\UserManagement\Helpers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Iquesters\UserManagement\Models\UserMeta;

class RegistrationHelper extends BaseAuthHelper
{
    /**
     * Handle user registration from traditional form or OAuth
     * 
     * @param string $name
     * @param string $email
     * @param string $password (optional - for traditional registration)
     * @param bool $email_verified
     * @param array $meta (optional - for OAuth data like google_id, avatar, etc)
     * @return User
     */
    public static function register_user(
        string $name,
        string $email,
        ?string $password = null,
        bool $email_verified = false,
        array $meta = []
    ): User {
        Log::debug('Registering new user for email: ' . $email);

        // Check if user already exists
        $user = User::where('email', $email)->first();

        if ($user) {
            Log::debug('User already exists: ' . $email);
            return $user;
        }

        // Create new user
        $user = User::create([
            'uid' => Str::ulid(),
            'name' => $name,
            'email' => $email,
            'status' => 'active',
            'password' => $password ? Hash::make($password) : bcrypt(Str::random(16)),
        ]);

        // Mark email as verified if needed
        if ($email_verified) {
            $user->markEmailAsVerified();
        }

        // Assign default role
        $user->assignRole(config('usermanagement.default_user_role', 'user'));

        // Save registration device/browser/IP info
        self::save_registration_info($user->id);

        // Save additional metadata (google_id, logo, etc)
        if (!empty($meta)) {
            foreach ($meta as $key => $value) {
                UserMeta::create([
                    'ref_parent' => $user->id,
                    'meta_key' => $key,
                    'meta_value' => $value,
                    'status' => 'active',
                ]);
            }
        }

        // Fire registered event
        event(new Registered($user));

        Log::debug('User created successfully: ' . $email);

        return $user;
    }

    /**
     * Save registration device and browser information
     * 
     * @param int $userId
     * @return void
     */
    protected static function save_registration_info(int $userId): void
    {
        // Save registration timestamp
        UserMeta::create([
            'ref_parent' => $userId,
            'meta_key' => 'registered_at',
            'meta_value' => Carbon::now()->toDateTimeString(),
            'status' => 'active',
        ]);

        // Save registration IP Address
        self::save_user_meta($userId, 'registration_ip_address', self::get_client_ip());

        // Save User Agent (raw browser/device string)
        self::save_user_meta($userId, 'registration_user_agent', self::get_user_agent());

        // Save Country/Locale/Timezone
        self::save_user_meta($userId, 'registration_country', self::get_country());
        self::save_user_meta($userId, 'registration_locale', self::get_locale());
        self::save_user_meta($userId, 'registration_timezone', self::get_timezone());
    }
}