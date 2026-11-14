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
 * - configured: the setup form was submitted (an APP_NAME other than the
 *   seeded default saved), or the app already has users (existing installs
 *   that never ran /setup).
 * - adminCreated: a super-admin exists, so onboarding is complete.
 *
 * "configured" never goes back to false, so once true it is cached forever
 * and costs no DB query on later requests.
 */
final class SetupState
{
    use Loggable;

    private const CACHE_KEY = 'usermanagement.setup_configured';

    /**
     * APP_NAME written by `user-interface:seed` (UserInterfaceConf default).
     * The seeder stores every default, so this value alone doesn't mean the
     * setup form was submitted.
     */
    private const SEEDED_DEFAULT_APP_NAME = 'Iquesters';

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
            if (! Schema::hasTable('master_data') || ! Schema::hasTable('master_data_metas')) {
                return false;
            }

            $confId = DB::table('master_data')->where('key', 'user-interface-conf')->value('id');

            return $confId !== null && DB::table('master_data_metas')
                ->where('ref_parent', $confId)
                ->where('meta_key', 'APP_NAME')
                ->whereNotNull('meta_value')
                ->where('meta_value', '<>', '')
                ->where('meta_value', '<>', self::SEEDED_DEFAULT_APP_NAME)
                ->exists();
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