<?php

namespace Iquesters\UserManagement\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Iquesters\Foundation\System\Traits\Loggable;
use Spatie\Permission\Models\Role;

/**
 * First-run setup state.
 *
 * - configured: the setup form was submitted (an app name is stored in the
 *   user-interface settings; only changed values are stored, never the
 *   default), or the app already has users (existing installs that never
 *   ran /setup).
 * - adminCreated: a super-admin exists, so onboarding is complete.
 *
 * "configured" never goes back to false, so once true it is cached forever
 * and costs no DB query on later requests.
 */
final class SetupState
{
    use Loggable;

    private const CACHE_KEY = 'usermanagement.setup_configured';

    public static function isConfigured(): bool
    {
        if (Cache::get(self::CACHE_KEY) === true) {
            return true;
        }

        $configured = self::appNameSaved() || self::hasUsers();

        if ($configured) {
            Cache::forever(self::CACHE_KEY, true);
            static::logInfo('Application setup detected as complete');
        }

        return $configured;
    }

    public static function markConfigured(): void
    {
        Cache::forever(self::CACHE_KEY, true);
        static::logInfo('Application setup marked as complete');
    }

    public static function adminCreated(): bool
    {
        try {
            return Schema::hasTable('roles')
                && Role::where('name', 'super-admin')->whereHas('users')->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private static function appNameSaved(): bool
    {
        try {
            $stored = \Iquesters\Foundation\Support\Settings\SettingsStore::get(\Iquesters\Foundation\Enums\Module::USER_INFE);

            return trim((string) ($stored['app_name'] ?? '')) !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    private static function hasUsers(): bool
    {
        try {
            return Schema::hasTable('users') && DB::table('users')->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}