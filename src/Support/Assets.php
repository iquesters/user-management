<?php

namespace Iquesters\UserManagement\Support;

use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\Foundation\Support\ModuleAssets;

/**
 * This module's public/ files: route, URLs (see foundation ModuleAssets).
 */
final class Assets extends ModuleAssets
{
    protected static function slug(): string
    {
        return 'usermanagement';
    }

    /** Logo from the user-management conf. */
    public static function logoUrl(): string
    {
        return self::resolveUrl((string) ConfProvider::from(Module::USER_MGMT)->logo, allowAppPublic: false);
    }
}
