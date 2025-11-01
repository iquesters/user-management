<?php

namespace Iquesters\UserManagement\Helpers;

use App\Models\User;
use Carbon\Carbon;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\Foundation\Enums\Module;
use Iquesters\UserManagement\Config\UserManagementKeys;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Iquesters\UserManagement\Models\UserMeta;
use Illuminate\Support\Str;

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
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            Log::debug('User already exists: ' . $email);
            if (empty($existingUser->uid)) {
                $existingUser->update(['uid' => self::generate_uid()]);
            }
            return $existingUser;
        }

        // ✅ Determine role BEFORE user creation
        $existingUserCount = User::count();
        $roleToAssign = null;

        // ✅ Ensure super-admin role exists
        $superAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => 'super-admin'],
            ['guard_name' => 'web']
        );

        if ($existingUserCount === 0) {
            // This is the first-ever user
            $roleToAssign = $superAdminRole;
            Log::info("Preparing to assign 'super-admin' role to first registered user: {$email}");
        } else {
            // Use configured default role
            $defaultRole = ConfProvider::from(Module::USER_MGMT)->default_user_role;

            if (!$defaultRole) {
                throw new \RuntimeException("Default user role is not configured.");
            }

            $roleToAssign = \Spatie\Permission\Models\Role::where('name', $defaultRole)->first();

            if (!$roleToAssign) {
                $message = "Default user role '{$defaultRole}' not found in database. Please seed or create it.";
                Log::error($message);
                throw new \RuntimeException($message);
            }
        }

        // ✅ Only now, create the user (safe to persist)
        $user = User::create([
            'uid' => self::generate_uid(),
            'name' => $name,
            'email' => $email,
            'status' => 'active',
            'password' => $password ? Hash::make($password) : bcrypt(Str::random(16)),
        ]);

        if ($email_verified) {
            $user->markEmailAsVerified();
        }

        // ✅ Assign validated role
        $user->assignRole($roleToAssign);
        
        // 🆕 If super admin, assign all modules automatically
        if ($roleToAssign->name === 'super-admin') {
            try {
                $modules = \Iquesters\Foundation\Models\Module::active()->get();

                foreach ($modules as $module) {
                    $assignedRoles = $module->getAssignedRoleIds() ?? [];

                    if (!in_array($roleToAssign->id, $assignedRoles)) {
                        $assignedRoles[] = $roleToAssign->id;
                        $module->assignRoles($assignedRoles);
                    }
                }

                \Log::info("✅ All modules assigned to 'super-admin' automatically after first user registration.", [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'total_modules' => $modules->count(),
                ]);

            } catch (\Throwable $e) {
                \Log::error("⚠️ Failed to auto-assign modules to super-admin", [
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        // Save registration info and meta
        self::save_registration_info($user->id);

        foreach ($meta as $key => $value) {
            UserMeta::create([
                'ref_parent' => $user->id,
                'meta_key' => $key,
                'meta_value' => $value,
                'status' => 'active',
            ]);
        }

        // Fire Registered event
        event(new Registered($user));

        Log::debug('User created successfully with role: ' . $roleToAssign->name);

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