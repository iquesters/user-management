<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Layout Configuration
    |--------------------------------------------------------------------------
    |
    | By default, the package uses its own layout (usermanagement::layouts.package).
    | You may override this by publishing this config file and/or setting env.
    |
    */
    'layout' => env('USER_MANAGEMENT_LAYOUT', 'usermanagement::layouts.package'),
];