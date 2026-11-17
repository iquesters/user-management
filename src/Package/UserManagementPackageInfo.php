<?php

namespace Iquesters\UserManagement\Package;

use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\System\Package\PackageInfo;

/**
 * Auto-discovered by BaseServiceProvider: migrations, views, routes/web.php,
 * the user-management:seed command (database/seeders/UserManagementSeeder),
 * Http/Middleware aliases and Config/UserManagementConf.
 */
class UserManagementPackageInfo extends PackageInfo
{
    protected function definePackageInfo(): void
    {
        // Views are referenced as usermanagement::… (not user-management::…).
        // routes/web.php pulls in auth.php and web/admin.php itself.
        $this->view_namespace = 'usermanagement';

        // The Module constant is USER_MGMT, not the auto-derived USER-MANAGEMENT.
        $this->auto_conf_module = Module::USER_MGMT;
    }
}
